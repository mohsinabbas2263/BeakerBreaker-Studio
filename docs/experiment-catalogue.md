# Experiment catalogue from the textbooks

Draft, 8 Nov 2026. Built from the eight textbooks in the working copy (not in git): text
extraction for Punjab 10, 11 and 12, and Windows OCR for Punjab 9 and Federal 9 to 12.
Every row was found in the book text; `Chemistry_Experiments_Compilation.txt` was used
only as a pointer and turned out to be incomplete.

Only hands-on lab work is listed. Paper activities (reading a periodic table, completing
isotope diagrams) are left out. Page numbers are PDF pages.

## Class 9

| # | Experiment | Board | Source |
|---|---|---|---|
| 1 | Saturated and unsaturated sugar solution | Punjab p16, Federal Act 2.1 p21 | both |
| 2 | Effect of heat on hydrated copper(II) sulphate (blue to white and back) | Punjab p89, Federal p140 | both |
| 3 | Acidic or basic? Household substances with litmus | Punjab Act 7.1 p99 | punjab |
| 4 | Zinc with dilute H₂SO₄, test hydrogen with a burning splint | Punjab Act 7.2 p104 | punjab |
| 5 | Unblocking a drain with baking soda and vinegar | Punjab Act 7.3 p105 | punjab (virtual only, `SR-09`) |
| 6 | Redox: FeSO₄ decolourises acidified KMnO₄ | Federal Act 7.1 p122 | federal |
| 7 | Redox: KI with H₂O₂ releases iodine | Federal Act 7.2 p122 | federal |
| 8 | Detect water with anhydrous copper(II) sulphate | Federal Act 12.1 p167 | federal |
| 9 | Purity of water by melting point | Federal Act 12.2 p168 | federal |
| 10 | Purity of water by boiling point | Federal Act 12.3 p169 | federal |
| 11-16 | Gas tests: NH₃ (red litmus), CO₂ (lime water), Cl₂ (blue litmus bleached), H₂ (pop), O₂ (glowing splint), SO₂ (acidified KMnO₄) | Federal Unit 18 pp237-241 | federal |

Also useful: Punjab 9 Chapter 12 (lab tools, measurement) and Chapter 13 (lab safety,
hazard signs, PPE, emergencies) for the safety tier question banks.

## Class 10

| # | Experiment | Board | Source |
|---|---|---|---|
| 1 | Diffusion: HCl and NH₃ in a long tube, white ring of NH₄Cl | Punjab Act 14.2 p15 | punjab |
| 2 | Rate of reaction: limestone with HCl, CO₂ in a gas syringe | Punjab Act 17.1 p62 | punjab |
| 3 | Pure crystals of sodium sulphate (titration, then crystallisation) | Punjab Act 18.1 p74 | punjab |
| 4 | Pure crystals of zinc sulphate (excess metal) | Punjab Act 18.2 p75, Federal Act 6.2 p77 | both |
| 5 | Prepare 250 cm³ of 0.1 M HCl by dilution | Federal Act 3.1 p40 | federal |
| 6 | Standardise HCl with 0.1 M NaOH (titration) | Federal Act 3.2 p41 | federal |
| 7 | Sodium chloride from HCl and NaOH by titration | Federal Act 6.1 p77 | federal |
| 8 | Copper(II) sulphate from CuO and H₂SO₄ | Federal Act 6.3 p78 | federal |
| 9 | Calcium chloride from CaCO₃ and HCl | Federal Act 6.4 p78 | federal |
| 10 | Reactivity series: Mg, Zn, Fe, Cu in dilute HCl | Federal Act 7.1 p91 | federal |
| 11 | Saturated vs unsaturated: bromine water with oil and butter | Federal Act 9.1 p116 | federal |
| 12 | Acetic acid with sodium and with sodium carbonate | Federal Act 11.1 p132 | federal |

## Class 11

| # | Experiment | Board | Source |
|---|---|---|---|
| 1 | Filtration (folding paper, Gooch and sintered crucibles) | Punjab Ch 15 p1001 | punjab |
| 2 | Crystallisation and drying of crystals | Punjab Ch 15 p1003 | punjab |
| 3 | Simple and fractional distillation | Punjab Ch 15 p1005 | punjab |
| 4 | Paper chromatography and Rf values | Punjab Ch 15 p1007 | punjab |
| 5 | Standardise NaOH with HCl (titration) | Punjab Ch 16 p1017 | punjab |
| 6-9 | Acid radicals: CO₃²⁻, Cl⁻/Br⁻/I⁻, NO₃⁻, SO₄²⁻ | Punjab Ch 16 pp1018-1020 | punjab |
| 10-15 | Basic radicals: Al³⁺, NH₄⁺, Ca²⁺, Cr³⁺, Cu²⁺, Fe³⁺, Zn²⁺ | Punjab Ch 16 pp1021-1023 | punjab |
| 16 | Synthesis of aspirin (salicylic acid, acetic anhydride) | Federal Activity p91 | federal |
| 17 | Air quality with indoor plants (classroom investigation, not a bench experiment) | Federal 13.8.5 p267 | federal |

Game-format content for Class 11 is in `backend/database/seeders/game/class-11.php`
(not seeded yet): salt and sand (filtration and crystallisation, rows 1-2), fractional
distillation, paper chromatography, NaOH standardisation, acid radicals (all four),
basic radicals (Fe³⁺, Cu²⁺, Cr³⁺, Al³⁺/Zn²⁺, NH₄⁺; Ca²⁺ still to add), and aspirin.

The compilation also lists a laboratory preparation of ammonia for Federal 11; it was not
found in the OCR text and is left out until someone checks the book by eye.

## Class 12

| # | Experiment | Board | Source |
|---|---|---|---|
| 1 | Oxidising and reducing agents: FeSO₄ with acidified KMnO₄ | Federal Act 2.1 p24 | federal |
| 2 | Dissolved oxygen by Winkler titration | Federal Act 2.2 p43 | federal |
| 3 | Partition coefficient with a separating funnel | Federal Act 3.1 p61 | federal |
| 4 | Redox titration: Fe²⁺ (iron tablet) with KMnO₄ | Federal p119 | federal |
| 5 | Chromatography of ink samples (forensic scenario) | Federal p304-305 | federal |

The Punjab 2nd year book has no lab activities. Fehling's, Tollens' and Brady's tests are
discussed in its organic chapters (the current seeder lists them as Class 9-10; they belong
to Class 12).

## Decision, 8 Nov 2026: textbook experiments only

Only experiments that appear in these textbooks are offered. `ExperimentSeeder::TEXTBOOK`
publishes the 10 seeded experiments that match a book entry, with that book's class and
board; the other 20 stay in the database unpublished. The book experiments that have no
seeded version yet are the content still to write.

Consequence today: a **Punjab Class 9 or 10 student sees one experiment** (the saturated
solution one, in both boards). The Punjab 9 and 10 activities (copper sulphate heating,
litmus, zinc and acid, diffusion, rate of reaction, sodium and zinc sulphate crystals) are
the first content to write for a Punjab school.

## The 30 seeded experiments against the books

Judged by title and topic; each still needs its steps compared with the book page.

| Seeded (`slug`, current class) | Nearest book entry | Suggested class and board |
|---|---|---|
| `melting-points` (9), `boiling-points` (9) | Purity of water by melting and boiling point, Federal 9 Act 12.2, 12.3 | 9, federal |
| `solubility-temperature` (9) | Saturated and unsaturated solution, 9 both | 9, both |
| `copper-sulphate-crystals` (9) | CuSO₄ from CuO, Federal 10 Act 6.3; crystallisation, Punjab 11 Ch 15 | 10, federal |
| `dilution-hcl` (9) | 0.1 M HCl by dilution, Federal 10 Act 3.1 | 10, federal |
| `standardise-naoh` (10), `standardise-hcl` (10) | Federal 10 Act 3.2; Punjab 11 Ch 16 | 10 federal and 11 punjab |
| `acid-base-neutral` (10) | NaCl by titration, Federal 10 Act 6.1 | 10, federal |
| `displacement-reactions` (10) | Reactivity series, Federal 10 Act 7.1 | 10, federal |
| `kmno4-saturation-test` (10) | Bromine water with oil and butter, Federal 10 Act 9.1 (different reagent) | 10, federal |
| `confirmatory-ion-tests` (10) | Acid and basic radicals, Punjab 11 Ch 16 | 11, punjab |
| `aldehydes-fehling-tollens`, `ketones-dnph`, `carboxylic-acids-test`, `phenol-fecl3-test` (10) | Organic tests in the Class 12 books, no lab activity | 12, both |
| `magnetic-separation`, `sublimation-ammonium-chloride`, `sublimation-naphthalene`, `dilution-naoh`, `dilution-na2co3`, `molarity-na2co3`, `molarity-oxalic-acid`, `solution-conductivity`, `endothermic-processes`, `flame-test`, `weak-acids-natural`, `sugar-decomposition`, `water-softening`, `electrolysis-water`, `copper-plating` | Not found as activities in these textbooks; likely from the older practical notebooks | check against the practical notebooks |

Book experiments with no seeded version yet: copper sulphate heating and anhydrous test,
litmus on household substances, zinc and acid, the two Federal 9 redox activities, the six
gas tests, diffusion of HCl and NH₃, rate of reaction, sodium and zinc sulphate crystals,
CaCl₂ preparation, acetic acid reactions, the Punjab 11 separation methods and radicals,
aspirin, and all of Class 12.

## Gaps

- The **practical notebooks** for each board and class are not here. Board practical exams
  follow them, so they are the main source still missing.
- The 30 seeded experiments need matching against this list and their `grade_level` and
  `board` corrected.
