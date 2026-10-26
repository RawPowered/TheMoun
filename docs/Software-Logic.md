# Software logic

[All sections](INDEX.md) · [Original presentation files](../presentation/) · [Review-only notice](../NOTICE.md)

Reading edition extracted from the supplied built presentation on October 25, 2026. Interactive controls are represented as text and initial displayed values; this is not a live service or a verified engineering, financial, or manufacturing result. Original internal-review labels are retained as historical document context.

---

## Software logic

Control-loop sandbox plus per-component firmware notes keyed to schematic refdes (`systemElectricalModel.ts`). Replace pseudo-code with your production HAL; validate on hardware.

### Live physics sandbox

PITCH: **0.00**° ERROR: **0.0000**

Manual disturbExecute auto-level

### IMU / level loop (reference)

Cross-reference U1  + U3  in the system schematic. This block is the conceptual glue between filtered attitude and step generation.

```text
// Design intent — not drop-in production
float Kp = 2.4f;
float Kd = 1.1f;
float last_err = 0.0f;

float levelControllerUpdate(float pitch_deg) {
  const float setpoint = 0.0f;
  float err = setpoint - pitch_deg;
  float d = (err - last_err); // scale by dt in real code
  last_err = err;
  float u = Kp * err + Kd * d;
  return u; // → motion planner target angle or rate
}

// Anti-windup, integrator, and sat limits belong here once
// you characterize motor + mechanics on the bench.
```

Validate sample rate, anti-windup, and limit-switch interaction on hardware before locking gains. Log IMU vs encoder if you add a secondary reference.

Status:  Simulation sandbox only — not validated on device.

### Firmware by electrical block

Each card maps to a symbol on the system schematic. Edit scripts in `src/data/firmwareComponentScripts.ts`.

AllMCU coreSensorActuator pathInterfacePowerHarness / passive

U1 MCU core U_MCU

#### MCU — scheduler, I/O, and motion supervisor

Owns timing-critical step pulses, limit interlocks, IMU service loop, and LED PWM. Keep ISR work minimal; defer filtering to main loop where latency allows.

Expand

V5 GND I2C STEP DIR LED_PWM LIMIT

MCU / pins:  D3 · D4 · D6 · A4 · A5 · D10 · D11

```text
// --- init (run once) ---
void setup() {
  pinMode(STEP_PIN, OUTPUT);      // D3
  pinMode(DIR_PIN, OUTPUT);       // D4
  pinMode(LED_PWM_PIN, OUTPUT);   // D6 → timer PWM preferred
  pinMode(LIM_LOW_PIN, INPUT_PULLUP);   // D10
  pinMode(LIM_HIGH_PIN, INPUT_PULLUP);  // D11

  Wire.begin();                   // I2C @ 400 kHz (verify IMU max)
  Wire.setClock(400000);
  imuInitAndConfigure();          // see U_IMU

  motionPlannerInit();
  limitsInit();                   // see J_HALL
}

// --- main loop (~1–4 kHz IMU path target; measure on scope) ---
void loop() {
  if (!limitsOkForMotion()) {
    motionFreezeSafeState();
    ledSetFaultPattern();
    return;
  }

  ImuSample s = imuReadFiltered();   // U_IMU
  float pitch_deg = attitudeFromAccel(s);

  float cmd = levelControllerUpdate(pitch_deg);  // PID / PD — tune on rig
  motionPlannerSetTargetDeg(cmd);
  motionPlannerService();           // emits STEP/DIR via U_STEP timing

  ledUpdateInspectionBrightness(); // Q1 LED channel
}

// --- example: non-blocking step tick (call from Timer1 ISR or soft timer) ---
volatile int32_t step_residual;
void isrStepService() {
  if (step_residual == 0) return;
  digitalWrite(STEP_PIN, HIGH);
  // delay_ns per driver datasheet — use NOP or timer compare
  digitalWrite(STEP_PIN, LOW);
  step_residual += (step_dir_positive ? -1 : 1);
}
```

Hardware validation

- Confirm STEP high-time meets driver IC minimum; check at driver pin with scope.

- Prove limit pins read correct logic level when mechanism hits hard stops.

- Measure worst-case loop jitter when I2C and stepping coincide.

U3 Sensor U_IMU

#### IMU — I2C bring-up, scaling, and filtering

MPU-6050-class: wake device, set ranges, calibrate offsets on a known-flat fixture, then run complementary or Madgwick filter depending on CPU budget.

Expand

V5 GND I2C

MCU / pins:  A4 (SDA) · A5 (SCL)

```text
#define IMU_ADDR 0x68  // AD0 low — confirm on your PCB

bool imuInitAndConfigure() {
  if (!imuWriteReg(PWR_MGMT_1, 0x00)) return false;   // exit sleep
  delay(10);
  imuWriteReg(GYRO_CONFIG, 0x01);   // ±500 °/s example
  imuWriteReg(ACCEL_CONFIG, 0x01);  // ±4 g example
  imuWriteReg(CONFIG, 0x03);        // DLPF — trade bandwidth vs delay
  imuWriteReg(SMPRT_DIV, 4);        // sample rate = 1kHz / (1+div) (check datasheet)
  return true;
}

ImuSample imuReadFiltered() {
  int16_t ax, ay, az, gx, gy, gz;
  imuReadAccelGyro(&ax, &ay, &az, &gx, &gy, &gz);

  // Scale to physical units using datasheet sensitivities
  float ax_g = ax * ACCEL_LSB_PER_G;
  float ay_g = ay * ACCEL_LSB_PER_G;
  float az_g = az * ACCEL_LSB_PER_G;

  // Low-pass + optional median on az for pitch in single-axis dominant geometry
  static float az_filt;
  az_filt = 0.92f * az_filt + 0.08f * az_g;

  return { ax_g, ay_g, az_filt, gx, gy, gz };
}
```

Hardware validation

- Log raw vs filtered pitch during vibration; ensure no aliasing from LED PWM or motor chop.

- Store accel/gyro biases after thermal soak; document temperature range.

U4 Actuator path U_STEP

#### Stepper driver — STEP/DIR protocol (MCU-facing)

Firmware never toggles coil wires directly; it produces STEP edges and DIR level. Configure microstepping via MSx pins in hardware or GPIO if strapped to MCU.

Expand

VIN12 GND STEP DIR MOT

MCU / pins:  D3 → STEP · D4 → DIR

```text
volatile uint8_t step_dir_positive = 1;

void motionPlannerEmitStepPulse() {
  digitalWrite(DIR_PIN, step_dir_positive ? HIGH : LOW);
  delayMicroseconds(2);  // DIR setup before STEP — consult A4988/DVR8825 timing

  digitalWrite(STEP_PIN, HIGH);
  delayMicroseconds(2);  // >1 µs typical
  digitalWrite(STEP_PIN, LOW);
}

// S-curve or trapezoid profile in planner; ISR or cooperative tick calls EmitStepPulse
void motionPlannerService() {
  if (!plannerHasSteps()) return;
  uint32_t now = micros();
  if (now - last_step_us < plannerNextIntervalUs()) return;
  last_step_us = now;
  motionPlannerEmitStepPulse();
  plannerDecrementRemaining();
}
```

Hardware validation

- Verify motor current pot / reference matches NEMA11 thermal rise under enclosure.

- During estop, tri-state or disable driver per hardware interlock scheme (not shown).

J4 Interface J_HALL

#### Limit inputs — debounce, polarity, and motion interlock

Treat limits as safety-critical: sample with debouncing, and gate motion before pulses are issued. Prefer hardware OR of limits into enable if budget allows.

Expand

LIMIT GND

MCU / pins:  D10 · D11

```text
struct LimitState {
  uint8_t low_stable;
  uint8_t high_stable;
  uint32_t last_change_us;
};

LimitState lim;

void limitsInit() {
  lim = {};
}

bool limitsOkForMotion() {
  bool low = digitalRead(LIM_LOW_PIN) == ACTIVE_LOW_ASSERTED;
  bool high = digitalRead(LIM_HIGH_PIN) == ACTIVE_LOW_ASSERTED;

  // 5 ms debounce
  lim.low_stable = debounce(lim.low_stable, low, &lim.last_change_us, 5000);
  lim.high_stable = debounce(lim.high_stable, high, &lim.last_change_us, 5000);

  if (lim.low_stable || lim.high_stable) return false;
  return true;
}

void motionFreezeSafeState() {
  plannerAbort();
  digitalWrite(STEP_PIN, LOW);
  // Optionally: disable driver enable pin if routed
}
```

Hardware validation

- Fault-inject: hold each limit active while commanding motion; verify zero steps.

- Document hall vs mechanical switch polarity in silk + this doc.

Q1 Actuator path Q_LED

#### LED channel — PWM dimming and thermal guard

Use hardware PWM for constant ripple frequency. Implement soft-start and optional NTC derating if LED board shares heat with mechanics.

Expand

LED_PWM GND

MCU / pins:  D6 (PWM)

```text
void ledUpdateInspectionBrightness() {
  uint8_t level = userBrightness; // 0–255 from UI or preset
  level = gammaCorrect(level);    // perceptual linearity

  if (driverOvertempLatch) {
    level = min(level, THERMAL_CAP);
  }
  analogWrite(LED_PWM_PIN, level);
}

// Optional: sample MCU ADC on NTC divider if routed (not on current diagram)
uint8_t gammaCorrect(uint8_t x) {
  float xf = x / 255.0f;
  float g = powf(xf, 2.2f);
  return (uint8_t)(g * 255.0f);
}
```

Hardware validation

- Measure LED current vs PWM duty; ensure NFET gate drive is adequate at 5 V logic.

- Check for beat frequencies between PWM and camera exposure if imaging is co-mounted.

J3 Interface J_BRIDGE

#### Pogo bridge — pad mapping and hot-plug policy

Pads carry power and signals across the moving boundary. Firmware should assume intermittent contact during mate/de-mate: sequence power-up, validate continuity or heartbeat on the pod if applicable.

Expand

BRG

MCU / pins:  Pads 1–6 ↔ D3,D4,D6,A4,A5,D10 (example — re-sync with ECAD)

```text
// Example policy: only energize high-side loads after mate sense (if added)
enum BridgeMateState { UNKNOWN, OPEN, MATED };

BridgeMateState bridgeSense() {
  // If you add a presence pin or resistance sense, update here.
  // Otherwise: require user acknowledgment before enabling LED rail.
  return MATED;
}

void powerPolicyWithBridge(BridgeMateState s) {
  if (s != MATED) {
    analogWrite(LED_PWM_PIN, 0);
    motionFreezeSafeState();
  }
}

// Keep BRIDGE_PAD_ASSIGNMENTS[] in pinout.ts identical to ECAD net table.
```

Hardware validation

- Cycle mate 500×; log I2C NACK rate and reset recovery behavior.

- ESD: verify discharge path does not latch MCU brown-out.

U2 Power U_BUCK

#### Buck converter — optional telemetry (no firmware if stand-alone)

If EN/PG are routed to MCU, you can sequence loads and log faults. Otherwise this block is hardware-only from firmware’s perspective.

Expand

VIN12 V5 GND

```text
// Optional — only if PG or EN wired to MCU GPIO/ADC
bool rail5vHealthy() {
#if defined(PG_5V_PIN)
  return digitalRead(PG_5V_PIN) == HIGH; // polarity per IC
#else
  return true;
#endif
}

void powerOnSequence() {
  // delay until 5V in regulation before IMU init
  uint32_t t0 = millis();
  while (!rail5vHealthy() && millis() - t0 < 50) { /* wait */ }
  imuInitAndConfigure();
}
```

Hardware validation

- Capture 5 V ripple at full stepper load; adjust LC per buck datasheet.

- If PG exists: inject undervoltage on 12 V input; confirm MCU enters safe mode.

J1 Power J_PWR

#### DC input — supervisor and brown-out behavior

If you add a divider to ADC for 12 V sense, scale carefully. BOR fuse bits on AVR should match minimum operating voltage of radio/IMU choice.

Expand

VIN12 GND

```text
// Optional 12 V sense on ADC (example — not on base schematic)
float readVin12Volts() {
  uint16_t raw = analogRead(VIN_SENSE_PIN);
  float v = raw * (5.0f / 1023.0f) * VIN_DIVIDER_RATIO;
  return v;
}

void brownOutGuard() {
  if (readVin12Volts() < 10.5f) {
    motionFreezeSafeState();
    analogWrite(LED_PWM_PIN, 0);
    setSystemFault(FAULT_LOW_VIN);
  }
}
```

Hardware validation

- Document fuse rating vs wire gauge and connector series resistance.

- Cold start: log 5 V rise time vs IMU power-on reset requirements.

J2 Harness / passive J_MOT

#### Motor connector — harness integrity (firmware indirect)

Firmware does not address J2 directly; motion is validated via mechanical motion and current sense (if added). Include a checklist for phase order swap.

Expand

MOT

```text
// Phase discovery helper — run once in factory test mode
void motorWiringSelfTest() {
  digitalWrite(DIR_PIN, HIGH);
  for (int i = 0; i < 200; i++) {
    motionPlannerEmitStepPulse();
    delay(2);
  }
  // Operator confirms direction vs expected translation; invert DIR in EEPROM if needed.
}
```

Hardware validation

- Label 1A/1B on harness; photograph reference build for assembly travelers.
