# Pro manufacturing

[All sections](INDEX.md) · [Original presentation files](../presentation/) · [Review-only notice](../NOTICE.md)

Reading edition extracted from the supplied built presentation on October 25, 2026. Interactive controls are represented as text and initial displayed values; this is not a live service or a verified engineering, financial, or manufacturing result. Original internal-review labels are retained as historical document context.

---

Print-first · low part count

Moun / RawGraded Pro

## Pro manufacturing

Assembly, sourcing, and QC written for a static printed bay: commodity mousepad stock, LED task lighting, phone rest — no motor line, no bridge PCB in the base kit.

Updated 2026-04-09

Eco SKU — CM scope

Eco strip (simplest): contract scope is plastic parts + mousepad padding only — no custom LED harness assembly by CM. Retail kit includes two brand-kitted commodity USB strips (sourced separately). No shipped measurement or grading templates unless added to scope. CAD may show example overlays for communication only.

### Pro BOM (draft line IDs)

| Line | Domain | Description | Notes |
| --- | --- | --- | --- |
| HB-P-001 | Printed | Main bay shell (flat panels, light ribs) | FDM; orient large faces on bed; minimize overhangs on phone ledge. |
| HB-P-002 | Printed | Optional insert tray / guide clips (if shipping modular overlays) | Can be same material as shell; second color for contrast. |
| HB-M-001 | Material | Mousepad sheet — cut to nest outline | Pressure-sensitive adhesive or mechanical retainers; same class as full-station deck spec. |
| HB-E-001 | Electrical | LED strip or PCB bar, diffused, white ~4000–5500K target | USB 5V or 12V brick per strip vendor; add fuse/current limit per design rules. |
| HB-E-002 | Electrical | Cable / connector kit (USB-A/C pigtail as chosen) | Strain relief at bay exit — print-in channel or grommet. |
| HB-H-001 | Hardware | Screws, heat-set inserts (optional), rubber feet | Prefer standard M3 or self-tapping into plastic bosses; torque low to avoid boss shear. |
| HB-C-001 | Consumable | Isopropyl wipe kit for bed adhesion / cosmetic prep | Line item only if shipping kits; omit for print-your-own. |

### Contract manufacturer — indicative cost stack

Below is a structure for quoting a static Pro (Moun P) kit: one-time NRE, variable materials, assembly labor, and pack-out. Numbers are illustrative bands for internal planning (US-oriented CM or nearshore; MOQ 500–2k units). Your CM will replace them with binding line items.

Unit economics move sharply below ~300–500 MOQ because NRE and line setup amortize over fewer pieces. Request tiered quotes at 500 / 1k / 2k / 5k.

Currency: USD

Variable stack (per kit)

$19.40 – $57.20

Materials + assembly + pack + illustrative OH

One-time NRE (total band)

$550.00 – $3,000.00

First article + pack art — amortize over MOQ

Example @ 1,000 units

+$0.55 – $3.00 NRE / unit

Indicative landed mfg floor ≈ variable + NRE amort (ex freight to you)

| Bucket | Line | Type | Low | High | Basis / notes |
| --- | --- | --- | --- | --- | --- |
| NRE / startup | First article, work instructions, fixture check (one-time) | One-time | $400.00 | $2,200.00 | Depends on CM engineering touch time; simple tray assembly stays low. |
| NRE / startup | Packaging dieline + label proof (one-time) | One-time | $150.00 | $800.00 | Skip or DIY if shipping bulk inner packs. |
| Materials | Printed bay shells (outsourced FDM or CM cell) | Per unit | $6.50 | $16.00 | Grams, material (PLA/PETG), scrap, and regional labor to run printers. |
| Materials | Mousepad blank + cut + adhesive | Per unit | $2.00 | $5.50 | Volume pricing on roll stock + die or CNC cut. |
| Materials | LED strip / bar + diffuser + USB harness | Per unit | $2.50 | $7.00 | COTS from approved vendors; includes connectorization. |
| Materials | Hardware kit (fasteners, feet, optional inserts) | Per unit | $0.60 | $2.20 | Buy at reel scale; kitting fee may be separate. |
| Assembly | Direct labor (trim, light install, bond pad, test) | Per unit | $4.50 | $14.00 | Assume 12–28 loaded minutes at $23–45/hr burdened rate; tighten with time study. |
| Assembly | QC sampling + burn-in (allocated) | Per unit | $0.50 | $2.00 | Higher if 100% functional test on LEDs. |
| Logistics | Inner carton, insert, slip sheet | Per unit | $0.80 | $2.50 | Plain kraft vs retail color box. |
| Overhead / margin | CM SG&A + profit (typical % on COGS — illustrative) | Per unit | $2.00 | $8.00 | Negotiated; some CMs quote all-in landed. |

Not a quote, offer, or GAAP cost statement. Replace ranges with executed CM quotes, actual BOM, freight, duties, and payment terms. Add inbound freight to CM and any inbound inspection.

### Assembly sequence

- 1

Print & trim

Run shell and optional clip parts; remove brim/supports; deburr slot edges that touch phone or card edges.

- 2

Dry fit

Check mousepad blank against nest; sand high spots on print if the pad must sit coplanar for photography.

- 3

Light install

Mount LED strip per channel design; dress cables to the exit; verify polarity and heat at connectors before final glue.

- 4

Deck bond / retain

Adhere or mechanically retain mousepad per adhesive datasheet; clamp or weight flat until set.

- 5

Power test

Energize from intended USB source; confirm no hot spots on flex areas; re-torque fasteners after thermal soak if specified.

### Print guidance (FDM)

- Orientation

Lay wide panels flat; ribs vertical for Z strength on the short phone-ledge cantilever.

- Walls / infill

Start from 3+ perimeters and 15–25% infill for shallow trays; increase if ledge flexes under phone weight.

- Material

PETG or PLA+ for stiffness vs ease; ABS/ASA only if enclosure tolerates fumes and warp control.

- Post-process

Sand visible faces; optional vapor smoothing only where material datasheet allows and ventilation is safe.

### Kit QC checks

- Phone rests without rocking; camera clears intended capture angle.

- LED field is even across mousepad — no dead segments in strip.

- No burrs in card path or phone corners that could snag labels.

- Cable exit passes pull test without cracking printed bosses.

- Document serial or batch on kit card if shipping pre-printed units.

This page is parallel to the main Manufacturing BOM tool (full station). Pro (Moun P) SKUs do not share the same cost domains until you map HB-* lines into that schema. Replace placeholder line codes when EBOM is frozen.
