# Investment calculator

[All sections](INDEX.md) · [Original presentation files](../presentation/) · [Review-only notice](../NOTICE.md)

Reading edition extracted from the supplied built presentation on October 25, 2026. Interactive controls are represented as text and initial displayed values; this is not a live service or a verified engineering, financial, or manufacturing result. Original internal-review labels are retained as historical document context.

---

Deal room tool

## Investment calculator

Start with the quick capital → units → contribution strip, then use the full model below for check size, pre-money, sell-through, and optional financing.

Step 1

### Set variant and channel

Choose Moun E or Cobra first, then pick sales channel assumptions before touching order-size sliders.

Step 2

### Adjust capital and tranche size

Enter investment and pre-money, then model order and units sold to understand money-in versus contribution outcomes.

Step 3

### Read decision metrics

Focus on break-even units, attributed contribution, and ownership context to frame realistic tranche terms.

Planning model only. Replace placeholder channel fees, CM assumptions, freight, duties, and warranty reserves with executed terms before term-sheet decisions.

Quick model

### Capital → units → net contribution

Landed-cost presets follow the same CM bands as the detailed tool. Enter three shelf prices (retail partner path, your online price, and third-party list); the active channel picks which price drives net revenue. Net contribution is units × (net revenue − landed); it is not cash-on-cash ROI, tax, or distributions.

VariantMoun EMoun E CobraMOQ band (landed curve)500 units1,000 units2,000 units

Landed cost preset (per unit)

Stress (high landed)Fair (mid)Upside (low landed)

Using $33.05 landed @ 1,000 MOQ · one-time NRE $1,600 (shown for reference only)

Capital for production (USD) [Displayed default: 75000] Subtract one-time NRE from capital before counting units (planning)

Sell price by channel (USD shelf / list)

Net-to-brand uses planning channel mechanics: DTC / own checkout: ~97% of shelf Third-party marketplace: ~88% of shelf Wholesale → retail: ~50% of shelf

Retail partner path (shelf MSRP) [Displayed default: 39.99] Online retail · your store (DTC list) [Displayed default: 39.99] Third-party online retail (marketplace list) [Displayed default: 39.99]

Which channel drives this run?Retail partner (shelf)Online · your store (DTC)Third-party online

~3% all-in payment processing (planning)

If you invest

$75,000

You can produce (units)

2,269

@ $33.05 landed · net $38.79/unit before COGS

Planning net contribution

$13,024.06

2,269 × $5.74/unit contribution

Deal desk

### Moun E — what your check buys, and what comes back (planning)

Founder-led program with no closed outside round on the books yet — treat defaults as illustrative planning for early checks, like sizing a Kickstarter-style manufacturing tranche against a negotiated pre-money. Set check and pre-money, then size one PO and sell-through. The table separates (a) inventory / tranche planning contribution scaled by how much of the PO your check covers from (b) your cap-table equity slice of the same dollars — they diverge when the check is small vs a large pre-money. Not distributions, IRR, tax, or option-pool reality.

Calculation-wise: your time, effort, and small out-of-pocket are not summed as dollar inputs here. The model assumes you already own 100% before this check; pre-money is the planning placeholder for “value already in the company” (IP, product, de-risking) that you negotiate with investors — not an hours ledger. Investor basic % stays check ÷ (pre-money + check); the PO block maps that check to inventory economics only.

Key decisions

Contribution / unit

$5.74

Channel-adjusted margin

14.8%

Break-even units

6,037

Net vs money-in

-$28,910.00

First read: set variant, channel, CM stance, order units, and sold units. Use advanced tools only when you need financing structure comparisons.

View full model details

| Metric | Value | Note |
| --- | --- | --- |
| Your check | $75,000 | Cash in for cap table math |
| Pre-money valuation | $3,000,000 | Planning peg — not 409A |
| Post-money (basic) | $3,075,000 | — |
| Your equity (basic, pre–option pool) | 2.44% | Check ÷ (pre + check) |
| Implied $ for 1% post-money | $30,750 | — |
| Capital this PO needs | $34,650 | Landed × order + NRE |
| Check vs this PO | +$40,350 | Surplus vs this PO (unallocated) |
| Tranche funding share | 100.00% | min(1, check ÷ PO capital) — how much of this PO your check covers |
| Units in PO | 1,000 | — |
| Units sold (modeled) | 1,000 | — |
| Shelf / MSRP (ASP) | $39.99 | — |
| Channel | DTC / own checkout | ~3% all-in payment processing (planning) |
| Brand net / unit (after channel) | $38.79 | — |
| Landed / unit | $33.05 | — |
| Gross vs shelf | 17.35% | (ASP − landed) ÷ ASP |
| Contrib. margin / brand net | 14.8% | (Net − landed) ÷ net |
| Contribution / unit | $5.74 | — |
| Contribution @ units sold | $5,740 | — |
| Check-attributed contribution @ units sold | $5,740 | Planning contribution × min(1, check ÷ PO capital). If your check fully funds this PO, equals full contribution @ sold — not ROI. |
| Equity lens @ units sold | $140 | Basic post-money % × contribution @ sold. Not cash return; tiny % if check is small vs pre-money. |
| If entire PO sells — contribution | $5,740 | — |
| Check-attributed contribution if entire PO sells | $5,740 | Same tranche rule on “if full PO” contribution. |
| Equity lens if entire PO sells | $140 | Basic % × contribution if every unit in the PO sells. |
| Units purchasable after NRE from your check alone | 2,220 | Floor((check − one-time NRE) ÷ landed). Ignores that NRE is shared across the PO; use as a directional “how many units does this check size approximate.” |
| Break-even units (planning) | 6,037 | — |
| Net vs money in @ sold | -$28,910.00 | Contribution @ sold − money in |

Simple economics controls

These controls drive the default unit-economics view. Financing lenses are available in the advanced section below.

Flagship variant

Moun EMoun E Cobra

Cobra uses a slight landed-cost simplification factor vs Moun E until dedicated CM quote tables are finalized.

Sales channel (planning)

Landed cost stays the same; this changes your net revenue per unit after channel fees.

DTC / own checkoutThird-party marketplaceWholesale → retail

CM landed stance

Low (stress)Objectively fairOptimistic

Custom shelf price (ASP) Off: ASP is the planning Moun E price from the build guide. On: model promos, bundles, or a different shelf posture.

Units in this PO (order)  [Displayed default: 1000]

5001k2k10k100k1M10M

Units sold (can exceed PO to stress TAM)  [Displayed default: 1000]

5001k2k10k100k1M10M

Money in vs contribution

Money in

$34,650

Contribution @ sold

$5,740

If PO fully sold

$5,740

Read on this run

Break-even for this capital level is ~6,037 units sold — your order (1,000) is below that; 100% sell-through of the tranche can still leave the run underwater.

Objectively fair midpoint only. Money in = MOQ × landed + NRE at that scenario. Potential contribution assumes every unit in the MOQ sells at the planning ASP through the DTC channel model (~3% pay) — not proof of demand or velocity.

Optional financing lensExpand Collapse

Scenario preset

Deck defaults $75k check · $3M pre-money (illustrative) Small check · lower pre-money $5k check · $250k pre-money (toy cap table)

Preset resets when you change check or pre-money manually.

Investment (cash in)  [Displayed default: 75000] Pre-money valuation  [Displayed default: 3000000]

#### Reverse: target ownership → implied pre-money

Reverse mode: pick a fair ownership band for the check, then see what pre-money that implies. Useful when you agree on “about 8% for $75k” before fixing the rest of the round.

Target investor ownership (fully diluted, rough)

[Displayed default: 8]  [Displayed default: 8]

Implied pre-money if you hit target %:  $862,500

#### Royalty (optional)

Some structures pair equity with a gross-revenue royalty. Enable below to layer a planning royalty rate and optional annual gross — equity math above is unchanged.

Model a gross-revenue royalty alongside equity

Check-size signal

Within a band where ownership and check size can support a serious investor relationship — still subject to round dynamics, lead terms, and counsel.

Pricing to collectors and shops should track product value and COGS — not investor dilution anxiety. Use margin and volume to pay for growth; avoid “make the customer fund the cap table.”

Option pool, secondaries, and note conversions are not modeled here — real FD% will be lower; model with counsel.

For discussion only. This is not investment advice, a solicitation, or a valuation opinion. Securities laws and tax treatment depend on jurisdiction and instrument — involve qualified counsel before accepting funds.
