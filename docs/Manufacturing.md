# Manufacturing

[All sections](INDEX.md) · [Original presentation files](../presentation/) · [Review-only notice](../NOTICE.md)

Reading edition extracted from the supplied built presentation on October 25, 2026. Interactive controls are represented as text and initial displayed values; this is not a live service or a verified engineering, financial, or manufacturing result. Original internal-review labels are retained as historical document context.

---

## Manufacturing

BOM splits **material** and **production** for true unit manufacturing cost; **NRE** holds optional one-time items (e.g. in-house firmware) without polluting per-unit roll-up. Baseline lock, archives, and JSON export below; tooling chart is illustrative.

### Materials & manufacturing BOM

Material  = physical buys · Production  = labor & ops per unit · NRE  = one-time program (e.g. your firmware), excluded from unit roll-up.

Working lines

25

Material

$292.30

Production

$95.00

NRE (one-time)

$0.00

1 unit manufacturing

$387.30

Material + production

NRE (program):  $0.00 — excluded from unit total above.

All-in incl. NRE: $387.30

Baseline:  Not set — lock when this BOM matches a quote or ECAD release.

#### Workflow & backup

Lock baselineRestore baselineClear baseline

Export JSONImport JSONReset template

Archive snapshotExpand Collapse

Name

Note (optional)

Save current BOM to archive

Stored in `localStorage` in this browser. Export regularly.

All lines25 Material22 Production2 NRE (one-time)1

- MaterialProductionNRE (one-time)

Item name [Displayed default: Main frame assembly]

Line total

$85.00

Qty / unit [Displayed default: 1]

UoM [Displayed default: ea]

Cost ($) / unit [Displayed default: 85]

Engineering ref [Displayed default: Frame — OBJ / schematic]

Description, notes & sourcing

DescriptionChassis / portal per mechanical drawing — print or machine

NotesTighten tolerances on bearing bores before locking vendor

Vendor

MPN

- MaterialProductionNRE (one-time)

Item name [Displayed default: Staging bed / platform insert]

Line total

$22.00

Qty / unit [Displayed default: 1]

UoM [Displayed default: ea]

Cost ($) / unit [Displayed default: 22]

Engineering ref [Displayed default: Inspection bed]

Description, notes & sourcing

Description

Notes

Vendor

MPN

- MaterialProductionNRE (one-time)

Item name [Displayed default: Stepper motor NEMA 11]

Line total

$18.00

Qty / unit [Displayed default: 1]

UoM [Displayed default: ea]

Cost ($) / unit [Displayed default: 18]

Engineering ref [Displayed default: J2 / U4]

Description, notes & sourcing

Description

Notes

Vendor

MPN [Displayed default: TBD]

- MaterialProductionNRE (one-time)

Item name [Displayed default: Linear motion (leadscrew + nut block)]

Line total

$35.00

Qty / unit [Displayed default: 1]

UoM [Displayed default: kit]

Cost ($) / unit [Displayed default: 35]

Engineering ref [Displayed default: Motion stack]

Description, notes & sourcing

Description

Notes

Vendor

MPN

- MaterialProductionNRE (one-time)

Item name [Displayed default: Coupling motor to screw]

Line total

$6.00

Qty / unit [Displayed default: 1]

UoM [Displayed default: ea]

Cost ($) / unit [Displayed default: 6]

Engineering ref

Description, notes & sourcing

Description

Notes

Vendor

MPN

- MaterialProductionNRE (one-time)

Item name [Displayed default: Mechanical hardware kit]

Line total

$12.00

Qty / unit [Displayed default: 1]

UoM [Displayed default: kit]

Cost ($) / unit [Displayed default: 12]

Engineering ref [Displayed default: Fastener BOM]

Description, notes & sourcing

DescriptionM3 screws, washers, thread locker sample

Notes

Vendor

MPN

- MaterialProductionNRE (one-time)

Item name [Displayed default: Light diffuser / bezel]

Line total

$8.00

Qty / unit [Displayed default: 1]

UoM [Displayed default: ea]

Cost ($) / unit [Displayed default: 8]

Engineering ref [Displayed default: LED optical path]

Description, notes & sourcing

Description

Notes

Vendor

MPN

- MaterialProductionNRE (one-time)

Item name [Displayed default: Adjustable feet / pads]

Line total

$6.00

Qty / unit [Displayed default: 4]

UoM [Displayed default: ea]

Cost ($) / unit [Displayed default: 1.5]

Engineering ref

Description, notes & sourcing

Description

Notes

Vendor

MPN

- MaterialProductionNRE (one-time)

Item name [Displayed default: PCB fabrication + ENIG]

Line total

$45.00

Qty / unit [Displayed default: 1]

UoM [Displayed default: ea]

Cost ($) / unit [Displayed default: 45]

Engineering ref [Displayed default: Main logic board]

Description, notes & sourcing

Description

NotesAllocated panel cost ÷ units — adjust for real quote

Vendor

MPN

- MaterialProductionNRE (one-time)

Item name [Displayed default: MCU — ATmega328P (or module)]

Line total

$4.50

Qty / unit [Displayed default: 1]

UoM [Displayed default: ea]

Cost ($) / unit [Displayed default: 4.5]

Engineering ref [Displayed default: U1]

Description, notes & sourcing

Description

Notes

Vendor

MPN [Displayed default: ATmega328P-AU]

- MaterialProductionNRE (one-time)

Item name [Displayed default: Crystal + load caps + reset]

Line total

$1.20

Qty / unit [Displayed default: 1]

UoM [Displayed default: set]

Cost ($) / unit [Displayed default: 1.2]

Engineering ref [Displayed default: U1 support]

Description, notes & sourcing

Description

Notes

Vendor

MPN

- MaterialProductionNRE (one-time)

Item name [Displayed default: IMU MPU-6050 module or IC]

Line total

$3.80

Qty / unit [Displayed default: 1]

UoM [Displayed default: ea]

Cost ($) / unit [Displayed default: 3.8]

Engineering ref [Displayed default: U3]

Description, notes & sourcing

Description

Notes

Vendor

MPN

- MaterialProductionNRE (one-time)

Item name [Displayed default: Stepper driver (A4988 class)]

Line total

$2.50

Qty / unit [Displayed default: 1]

UoM [Displayed default: ea]

Cost ($) / unit [Displayed default: 2.5]

Engineering ref [Displayed default: U4]

Description, notes & sourcing

Description

Notes

Vendor

MPN

- MaterialProductionNRE (one-time)

Item name [Displayed default: Buck converter 12V→5V]

Line total

$2.20

Qty / unit [Displayed default: 1]

UoM [Displayed default: ea]

Cost ($) / unit [Displayed default: 2.2]

Engineering ref [Displayed default: U2]

Description, notes & sourcing

Description

Notes

Vendor

MPN [Displayed default: MP1584 module]

- MaterialProductionNRE (one-time)

Item name [Displayed default: Pogo pins / spring contacts]

Line total

$5.10

Qty / unit [Displayed default: 6]

UoM [Displayed default: ea]

Cost ($) / unit [Displayed default: 0.85]

Engineering ref [Displayed default: J3]

Description, notes & sourcing

Description6 positions bridge — match stack height

Notes

Vendor

MPN

- MaterialProductionNRE (one-time)

Item name [Displayed default: NFET + gate resistor LED drive]

Line total

$0.60

Qty / unit [Displayed default: 1]

UoM [Displayed default: set]

Cost ($) / unit [Displayed default: 0.6]

Engineering ref [Displayed default: Q1]

Description, notes & sourcing

Description

Notes

Vendor

MPN

- MaterialProductionNRE (one-time)

Item name [Displayed default: High-C bulk input capacitor]

Line total

$1.10

Qty / unit [Displayed default: 1]

UoM [Displayed default: ea]

Cost ($) / unit [Displayed default: 1.1]

Engineering ref [Displayed default: 12V rail]

Description, notes & sourcing

Description

Notes

Vendor

MPN

- MaterialProductionNRE (one-time)

Item name [Displayed default: Hall / limit sensors]

Line total

$4.80

Qty / unit [Displayed default: 2]

UoM [Displayed default: ea]

Cost ($) / unit [Displayed default: 2.4]

Engineering ref [Displayed default: J4 → D10/D11]

Description, notes & sourcing

Description

Notes

Vendor

MPN

- MaterialProductionNRE (one-time)

Item name [Displayed default: Connectors + harness materials]

Line total

$8.00

Qty / unit [Displayed default: 1]

UoM [Displayed default: lot]

Cost ($) / unit [Displayed default: 8]

Engineering ref [Displayed default: J1, J2]

Description, notes & sourcing

Description

Notes

Vendor

MPN

- MaterialProductionNRE (one-time)

Item name [Displayed default: LED assembly / COB strip segment]

Line total

$14.00

Qty / unit [Displayed default: 1]

UoM [Displayed default: ea]

Cost ($) / unit [Displayed default: 14]

Engineering ref [Displayed default: D6 PWM]

Description, notes & sourcing

Description

Notes

Vendor

MPN

- MaterialProductionNRE (one-time)

Item name [Displayed default: Passives assortment (res/cap/diode)]

Line total

$4.00

Qty / unit [Displayed default: 1]

UoM [Displayed default: lot]

Cost ($) / unit [Displayed default: 4]

Engineering ref

Description, notes & sourcing

Description

NotesRoll-up until pick-and-place BOM frozen

Vendor

MPN

- MaterialProductionNRE (one-time)

Item name [Displayed default: ESD packaging + labels]

Line total

$3.50

Qty / unit [Displayed default: 1]

UoM [Displayed default: lot]

Cost ($) / unit [Displayed default: 3.5]

Engineering ref

Description, notes & sourcing

Description

Notes

Vendor

MPN

- MaterialProductionNRE (one-time)

Item name [Displayed default: Final assembly labor]

Line total

$67.50

Qty / unit [Displayed default: 1.5]

UoM [Displayed default: hr]

Cost ($) / unit [Displayed default: 45]

Engineering ref [Displayed default: Manufacturing]

Description, notes & sourcing

DescriptionMechanical + electrical box build per traveler

Notes

Vendor

MPN

- MaterialProductionNRE (one-time)

Item name [Displayed default: Functional test & calibration labor]

Line total

$27.50

Qty / unit [Displayed default: 0.5]

UoM [Displayed default: hr]

Cost ($) / unit [Displayed default: 55]

Engineering ref [Displayed default: QC / Software validation]

Description, notes & sourcing

DescriptionIMU trim, motion limits, optical check — per unit

NotesLabor to verify firmware + hardware on each unit; not a software license cost

Vendor

MPN

- MaterialProductionNRE (one-time)

Item name [Displayed default: Embedded firmware (in-house)]

Line total

$0.00

Qty / unit [Displayed default: 1]

UoM [Displayed default: program]

Cost ($) / unit [Displayed default: 0]

Engineering ref [Displayed default: Software section]

Description, notes & sourcing

DescriptionYou develop — not an ongoing per-unit SKU cost

NotesOptional: enter total program NRE for quotes only. Excluded from “1 unit manufacturing” total.

Vendor

MPN

#### Quick add line

Open the card below to add description, vendor, and MPN.

MaterialProductionNRE (one-time)

Name

Qty / unit [Displayed default: 1]

UoM [Displayed default: ea]

$ / unit [Displayed default: 0]

Add to BOM

#### Saved archives

None yet. Use “Archive snapshot” in the panel above before big quote or design changes.

### Phase 1 — tooling amortization (illustrative)

Chart values are static examples; use the BOM above for per-unit rolling costs and export snapshots when quotes change.

#### QC protocol outline

Tie each line to a traveler step and sign-off. Numbers below are placeholders until qualification completes.

| Metric | Criterion | Method |
| --- | --- | --- |
| Full-range tilt cycles (life test) | 100 cycles | Automated rig script + log |
| IMU residual after calibration | ±0.05° (TBD on product spec) | Static fixture, temp noted |
| Bridge contact resistance | TBD mΩ max | Four-wire measurement |
| Optical center drift vs temperature | TBD | Environmental chamber protocol |

#### Material traceability

Mirror critical lots in BOM notes or archive snapshot when a build is frozen. Below is a reminder checklist.

Main gears / motion Lot + vendor on traveler

Bearing surfaces Material cert on file

Fasteners A2-70 + torque sheet
