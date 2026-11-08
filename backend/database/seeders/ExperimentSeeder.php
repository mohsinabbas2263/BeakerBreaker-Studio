<?php

namespace Database\Seeders;

use App\Models\Experiment;
use Illuminate\Database\Seeder;

/**
 * FR-03.1: no fewer than 30 experiments.
 * All 30 are aligned to the Punjab Textbook Board Class 9 and 10 chemistry
 * practicals. Every step carries preauthored_guidance (NFR-06). AR is
 * available on four experiments (FR-05.1): flame-test, electrolysis-water,
 * copper-plating, displacement-reactions.
 */
class ExperimentSeeder extends Seeder
{
    public function run(): void
    {
        $experiments = [

            // ── Class 9 ─────────────────────────────────────────────────────

            [
                'slug'             => 'magnetic-separation',
                'title'            => 'Magnetic Separation of Iron and Sulfur',
                'topic'            => 'Mixtures',
                'difficulty'       => 'easy',
                'grade_level'      => 9,
                'prerequisite_slug'=> null,
                'chemical_equation'=> 'No chemical change. Iron is ferromagnetic; sulfur is not. Separation exploits this difference in physical properties.',
                'explanatory_note' => 'Magnetic separation works because iron is ferromagnetic and attracted to a magnet while sulfur is not. The mixture is a physical mixture — no bonds are formed or broken. This method works only when one component responds to a magnetic field.',
                'ar_available'     => false,
                'base_points'      => 40,
                'estimated_minutes'=> 15,
                'emoji'            => '🧲',
                'card_gradient'    => 'linear-gradient(135deg,#E8F4FD,#4FC3F7)',
                'steps' => [
                    ['step_order' => 1, 'title' => 'Prepare the mixture', 'instruction_text' => 'Weigh equal masses of iron filings and powdered sulfur. Mix them thoroughly on a piece of white paper.', 'preauthored_guidance' => 'Mix well so both components are evenly distributed. An uneven mixture means the magnet may miss pockets of iron, giving an incomplete separation.', 'item_emoji' => '⚗️', 'action_label' => 'Mix powders'],
                    ['step_order' => 2, 'title' => 'Protect the magnet', 'instruction_text' => 'Slide a bar magnet inside a thin polythene bag before using it.', 'preauthored_guidance' => 'The bag keeps iron filings from adhering directly to the magnet. Without it, fine iron grains cling even after repeated shaking and contaminate future experiments.', 'item_emoji' => '🧲', 'action_label' => 'Cover magnet'],
                    ['step_order' => 3, 'title' => 'Pass the magnet through the mixture', 'instruction_text' => 'Move the bagged magnet slowly through the pile of mixture, making several passes.', 'preauthored_guidance' => 'Move slowly. The magnetic force acts over a short range; multiple deliberate passes collect more iron than one quick sweep.', 'item_emoji' => '🔄', 'action_label' => 'Sweep magnet'],
                    ['step_order' => 4, 'title' => 'Recover the iron', 'instruction_text' => 'Turn the polythene bag inside-out over a clean container to release the collected iron filings. Examine the sulfur left behind.', 'preauthored_guidance' => 'Invert the bag over the container before withdrawing the magnet — if you pull the magnet out first, the filings fall back into the sulfur.', 'item_emoji' => '📦', 'action_label' => 'Collect iron'],
                ],
                'quiz' => [
                    // correct_options: [0,2,1,3]
                    ['prompt' => 'What property of iron allows it to be separated magnetically from sulfur?', 'options' => ['It is ferromagnetic', 'It is denser than sulfur', 'It has a lower melting point', 'It dissolves in water'], 'correct_option' => 0, 'explanation' => 'Iron is ferromagnetic — it is strongly attracted to a magnet. Sulfur has no significant magnetic response.'],
                    ['prompt' => 'Why is the magnet placed inside a polythene bag?', 'options' => ['To increase the magnetic force', 'To protect the magnet from chemical attack', 'To prevent iron filings sticking directly to the magnet surface', 'To make the magnet easier to handle'], 'correct_option' => 2, 'explanation' => 'Direct contact causes fine iron filings to cling stubbornly. The bag allows the filings to be released cleanly by inverting it.'],
                    ['prompt' => 'Magnetic separation of iron from sulfur is a ___ change.', 'options' => ['Chemical', 'Physical', 'Nuclear', 'Thermal'], 'correct_option' => 1, 'explanation' => 'No new substances are formed; the iron and sulfur retain their original properties. Separation by physical means is a physical change.'],
                    ['prompt' => 'Which mixture could also be separated by a magnet?', 'options' => ['Salt and sand', 'Sugar and flour', 'Ethanol and water', 'Iron filings and sand'], 'correct_option' => 3, 'explanation' => 'Iron filings are attracted to a magnet; sand is not. The other pairs have no magnetic component.'],
                ],
            ],

            [
                'slug'             => 'melting-points',
                'title'            => 'Melting Point Determination',
                'topic'            => 'Physical Properties',
                'difficulty'       => 'easy',
                'grade_level'      => 9,
                'prerequisite_slug'=> null,
                'chemical_equation'=> 'Physical change: solid ⇌ liquid at the melting point. Pure naphthalene: 80 °C; pure benzoic acid: 122 °C.',
                'explanatory_note' => 'A pure substance has a sharp, well-defined melting point. Impurities lower the melting point and broaden the range. Melting point measurement is therefore both an identification tool and a purity test.',
                'ar_available'     => false,
                'base_points'      => 45,
                'estimated_minutes'=> 20,
                'emoji'            => '🌡️',
                'card_gradient'    => 'linear-gradient(135deg,#FFF9C4,#FFD54F)',
                'steps' => [
                    ['step_order' => 1, 'title' => 'Load the capillary tube', 'instruction_text' => 'Pack a small amount of the solid (naphthalene or benzoic acid) into a melting-point capillary tube to a depth of 2–3 mm.', 'preauthored_guidance' => 'Tap the closed end on the bench to compact the sample. A loosely packed sample gives a falsely broad melting range even for a pure substance.', 'item_emoji' => '🧪', 'action_label' => 'Load tube'],
                    ['step_order' => 2, 'title' => 'Attach the tube to the thermometer', 'instruction_text' => 'Use a rubber band to hold the capillary tube alongside the thermometer so the sample is level with the bulb.', 'preauthored_guidance' => 'The sample must be at the same height as the bulb. If it is above the bulb, the temperature reading will be lower than the true melting point.', 'item_emoji' => '🌡️', 'action_label' => 'Attach tube'],
                    ['step_order' => 3, 'title' => 'Heat the water bath slowly', 'instruction_text' => 'Heat quickly to within 10 °C of the expected melting point, then slow to about 1 °C per minute.', 'preauthored_guidance' => 'Slow heating near the melting point allows the thermometer to keep pace with the sample. A rapid rise causes the thermometer to lag, giving a reading that is too low.', 'item_emoji' => '🔥', 'action_label' => 'Heat bath'],
                    ['step_order' => 4, 'title' => 'Record the melting range', 'instruction_text' => 'Note the temperature at which the first drop of liquid appears and the temperature at which the last solid disappears.', 'preauthored_guidance' => 'A range of less than 1 °C indicates a pure substance. A wide range or an onset well below the literature value indicates impurity.', 'item_emoji' => '📋', 'action_label' => 'Record range'],
                ],
                'quiz' => [
                    // correct_options: [1,0,3,2]
                    ['prompt' => 'A substance that melts over a wide temperature range is most likely:', 'options' => ['Pure', 'Impure', 'A liquid at room temperature', 'An element'], 'correct_option' => 1, 'explanation' => 'Impurities disrupt the crystal lattice, causing melting to begin at a lower temperature and to occur over a range rather than at a sharp point.'],
                    ['prompt' => 'Why must heating be very slow near the expected melting point?', 'options' => ['To allow the thermometer reading to keep pace with the sample temperature', 'To save energy', 'To prevent the water from boiling', 'To dissolve the sample in water'], 'correct_option' => 0, 'explanation' => 'A rapid temperature rise causes the thermometer to lag behind the true sample temperature, giving a falsely low reading.'],
                    ['prompt' => 'The melting point of a pure substance is:', 'options' => ['A range of about 10 °C', 'Lowered by applied pressure', 'Variable depending on batch', 'Sharp and reproducible'], 'correct_option' => 3, 'explanation' => 'Purity is confirmed by a sharp melting point (less than 1 °C range) that matches the literature value.'],
                    ['prompt' => 'Adding an impurity to a pure substance will:', 'options' => ['Raise the melting point', 'Have no effect on the melting point', 'Lower the melting point and broaden the range', 'Sharpen the melting range'], 'correct_option' => 2, 'explanation' => 'Impurities lower the melting point (depression of freezing point) and broaden the range. This is the basis of the impurity test.'],
                ],
            ],

            [
                'slug'             => 'boiling-points',
                'title'            => 'Boiling Point Determination',
                'topic'            => 'Physical Properties',
                'difficulty'       => 'easy',
                'grade_level'      => 9,
                'prerequisite_slug'=> null,
                'chemical_equation'=> 'Physical change: liquid ⇌ vapour at the boiling point. Boiling point of water at sea level: 100 °C; ethanol: 78 °C.',
                'explanatory_note' => 'The boiling point is the temperature at which the vapour pressure of a liquid equals the external pressure. Pure liquids boil at a constant, characteristic temperature; mixtures boil over a range. The thermometer is placed at the side arm, not in the liquid, so it reads the vapour temperature.',
                'ar_available'     => false,
                'base_points'      => 45,
                'estimated_minutes'=> 20,
                'emoji'            => '💧',
                'card_gradient'    => 'linear-gradient(135deg,#E8F4FD,#4FC3F7)',
                'steps' => [
                    ['step_order' => 1, 'title' => 'Set up the distillation flask', 'instruction_text' => 'Clamp a round-bottomed flask containing the liquid and attach a condenser. Add an anti-bumping granule before heating.', 'preauthored_guidance' => 'Add the anti-bumping granule before the liquid is hot. Never add one to a heated liquid — sudden boiling can shatter the flask. The granule provides nucleation sites that prevent superheating.', 'item_emoji' => '⚗️', 'action_label' => 'Set up flask'],
                    ['step_order' => 2, 'title' => 'Position the thermometer', 'instruction_text' => 'Position the thermometer bulb level with the side arm leading to the condenser — not submerged in the liquid.', 'preauthored_guidance' => 'A bulb in the liquid reads the liquid temperature, not the vapour temperature. Only the vapour at the side arm gives the true boiling point.', 'item_emoji' => '🌡️', 'action_label' => 'Place thermometer'],
                    ['step_order' => 3, 'title' => 'Heat and observe', 'instruction_text' => 'Apply heat gently. Note when bubbles form throughout the liquid and the temperature becomes constant.', 'preauthored_guidance' => 'A pure liquid holds a constant temperature throughout boiling. If the temperature continues to rise, the liquid is a mixture — record the boiling range from start to when the flask is nearly empty.', 'item_emoji' => '🔥', 'action_label' => 'Heat liquid'],
                    ['step_order' => 4, 'title' => 'Record the boiling point', 'instruction_text' => 'Read the thermometer when the temperature has been steady for 30 seconds. This is the boiling point.', 'preauthored_guidance' => 'Record the temperature to the nearest 0.5 °C. Compare with the literature value; a large discrepancy suggests the thermometer is positioned incorrectly or the liquid is impure.', 'item_emoji' => '📋', 'action_label' => 'Record boiling point'],
                ],
                'quiz' => [
                    // correct_options: [2,3,0,1]
                    ['prompt' => 'Where should the thermometer bulb be positioned during simple distillation?', 'options' => ['Submerged in the boiling liquid', 'Above the top of the condenser', 'Level with the side arm leading to the condenser', 'Touching the flask wall below the liquid'], 'correct_option' => 2, 'explanation' => 'The thermometer reads the vapour temperature, not the liquid temperature. The vapour at the side arm gives the true boiling point of the distillate being collected.'],
                    ['prompt' => 'Boiling occurs when the vapour pressure of the liquid equals:', 'options' => ['The temperature of the liquid', 'The surface tension of the liquid', 'The density of the liquid', 'The external atmospheric pressure'], 'correct_option' => 3, 'explanation' => 'When vapour pressure equals external pressure, bubbles can form throughout the liquid, not just at the surface. This defines the boiling point.'],
                    ['prompt' => 'Why is an anti-bumping granule added to the flask?', 'options' => ['To prevent superheating and sudden violent boiling', 'To speed up the rate of boiling', 'To purify the liquid', 'To raise the boiling point'], 'correct_option' => 0, 'explanation' => 'The granule provides nucleation sites so bubbles form steadily. Without it, the liquid can superheat and then boil violently (bumping), which may break the flask.'],
                    ['prompt' => 'A mixture of two miscible liquids boils:', 'options' => ['At a constant temperature equal to their average', 'Over a range of temperatures', 'Above the higher boiling point of either component', 'Below the lower boiling point of either component'], 'correct_option' => 1, 'explanation' => 'Mixtures do not have a fixed boiling point. The composition of the vapour changes as one component evaporates preferentially, so the boiling temperature rises continuously.'],
                ],
            ],

            [
                'slug'             => 'sublimation-ammonium-chloride',
                'title'            => 'Sublimation of Ammonium Chloride',
                'topic'            => 'Physical Properties',
                'difficulty'       => 'easy',
                'grade_level'      => 9,
                'prerequisite_slug'=> null,
                'chemical_equation'=> 'NH₄Cl(s) ⇌ NH₄Cl(g) on heating; the vapour re-deposits as pure solid on a cool surface.',
                'explanatory_note' => 'Sublimation is the direct conversion of a solid to vapour without passing through the liquid state. Ammonium chloride sublimes readily on gentle warming. Re-deposition on a cool surface leaves non-volatile impurities behind, making sublimation a purification method.',
                'ar_available'     => false,
                'base_points'      => 50,
                'estimated_minutes'=> 20,
                'emoji'            => '🌫️',
                'card_gradient'    => 'linear-gradient(135deg,#C8F7E4,#4CAF82)',
                'steps' => [
                    ['step_order' => 1, 'title' => 'Place the sample in an evaporating dish', 'instruction_text' => 'Put a small amount of ammonium chloride in an evaporating dish. Note its appearance.', 'preauthored_guidance' => 'Use only a thumbnail-sized amount. A large sample produces dense fumes that irritate the throat. Work near a fume extraction point.', 'item_emoji' => '🥄', 'action_label' => 'Add sample'],
                    ['step_order' => 2, 'title' => 'Invert a funnel over the dish', 'instruction_text' => 'Place an inverted glass funnel over the dish, with cotton wool loosely plugging the stem.', 'preauthored_guidance' => 'Do not seal the funnel completely. The cotton wool slows vapour loss but pressure must still be able to escape. A sealed funnel may shatter.', 'item_emoji' => '🔺', 'action_label' => 'Cover with funnel'],
                    ['step_order' => 3, 'title' => 'Heat gently', 'instruction_text' => 'Apply low heat under the dish. White fumes will rise and condense as crystals on the inside of the funnel.', 'preauthored_guidance' => 'Low heat only — ammonium chloride sublimes at a low temperature. High heat wastes sample unnecessarily and produces more fumes than needed.', 'item_emoji' => '🔥', 'action_label' => 'Heat gently'],
                    ['step_order' => 4, 'title' => 'Collect the sublimate', 'instruction_text' => 'Remove the heat, allow the funnel to cool, then tap it to collect the white crystals from the inside.', 'preauthored_guidance' => 'The crystals inside the funnel are pure ammonium chloride. Any non-volatile impurity stayed in the dish. This is why sublimation is used as a purification method.', 'item_emoji' => '📦', 'action_label' => 'Collect crystals'],
                ],
                'quiz' => [
                    // correct_options: [3,1,2,0]
                    ['prompt' => 'Sublimation is the change of state directly from:', 'options' => ['Liquid to gas', 'Gas to solid', 'Liquid to solid', 'Solid to gas'], 'correct_option' => 3, 'explanation' => 'Sublimation bypasses the liquid state entirely — the solid converts directly to vapour on heating and re-deposits directly as solid on cooling.'],
                    ['prompt' => 'The white crystals that form inside the funnel are:', 'options' => ['A new compound formed by the heat', 'Purified ammonium chloride', 'Ammonium nitrate', 'Calcium chloride'], 'correct_option' => 1, 'explanation' => 'The vapour is pure NH₄Cl. On contact with the cool funnel surface it re-deposits as pure crystals, leaving any non-volatile impurities in the dish.'],
                    ['prompt' => 'Why must the funnel not be completely sealed?', 'options' => ['To let air in to support the reaction', 'To prevent the temperature rising too high', 'To allow pressure to escape and prevent the funnel shattering', 'To make the fumes more visible'], 'correct_option' => 2, 'explanation' => 'Vapour pressure builds inside a sealed vessel. If the funnel were sealed, pressure could shatter the glass.'],
                    ['prompt' => 'Sublimation is useful for purification because:', 'options' => ['Only the sublimable compound vaporises and re-deposits as pure solid, leaving non-volatile impurities behind', 'It melts impurities first so they can be poured away', 'It dissolves the pure compound in a solvent, separating it from insoluble residues', 'It converts the solid to a liquid that can then be filtered'], 'correct_option' => 0, 'explanation' => 'Because the sublimable compound passes through the vapour phase and re-deposits on the cool surface, non-volatile impurities cannot follow it and are left in the original dish.'],
                ],
            ],

            [
                'slug'             => 'sublimation-naphthalene',
                'title'            => 'Sublimation of Naphthalene',
                'topic'            => 'Physical Properties',
                'difficulty'       => 'easy',
                'grade_level'      => 9,
                'prerequisite_slug'=> null,
                'chemical_equation'=> 'C₁₀H₈(s) ⇌ C₁₀H₈(g); naphthalene has a measurable vapour pressure at room temperature; melting point 80 °C.',
                'explanatory_note' => 'Naphthalene sublimes slowly at room temperature and more quickly on gentle warming. Its vapour re-deposits as flat white crystals on a cool surface. The flammability of naphthalene vapour requires that all open flames are kept well away during the experiment.',
                'ar_available'     => false,
                'base_points'      => 50,
                'estimated_minutes'=> 20,
                'emoji'            => '❄️',
                'card_gradient'    => 'linear-gradient(135deg,#F3E5F5,#9575CD)',
                'steps' => [
                    ['step_order' => 1, 'title' => 'Observe room-temperature sublimation', 'instruction_text' => 'Place a few naphthalene flakes in an open dish and leave for five minutes. Observe any changes near the surface of the flakes.', 'preauthored_guidance' => 'Tiny flat crystals may appear just above the surface. In a warm room this is more noticeable. This shows sublimation occurs without any heating at all.', 'item_emoji' => '🔍', 'action_label' => 'Observe at room temp'],
                    ['step_order' => 2, 'title' => 'Set up the cold plate', 'instruction_text' => 'Transfer the naphthalene to an evaporating dish. Set a cold glass plate on a tripod about 5 cm above the dish.', 'preauthored_guidance' => 'Keep the plate several centimetres above the sample. Too close, the plate warms up and crystals do not form well. Too far, the vapour disperses before reaching it.', 'item_emoji' => '🔲', 'action_label' => 'Position plate'],
                    ['step_order' => 3, 'title' => 'Heat gently — no open flames', 'instruction_text' => 'Apply low heat. White vapour will rise and deposit flat crystals on the underside of the glass plate.', 'preauthored_guidance' => 'Naphthalene vapour is flammable. Use an electric hotplate or a water bath — never a Bunsen burner. Keep the laboratory ventilated throughout.', 'item_emoji' => '⚡', 'action_label' => 'Heat gently'],
                    ['step_order' => 4, 'title' => 'Examine the product', 'instruction_text' => 'Remove the glass plate and examine the crystals on its underside. Note their shape compared with the original flakes.', 'preauthored_guidance' => 'Crystals grown by sublimation and vapour-deposition are often more regular in shape than the original crushed flakes. Both are pure naphthalene — shape is a physical, not a chemical, difference.', 'item_emoji' => '🔬', 'action_label' => 'Examine crystals'],
                ],
                'quiz' => [
                    // correct_options: [0,3,1,2]
                    ['prompt' => 'Naphthalene sublimes at room temperature because it has:', 'options' => ['A measurable vapour pressure at room temperature', 'A boiling point below room temperature', 'No intermolecular forces', 'A density less than air'], 'correct_option' => 0, 'explanation' => 'Sublimation at room temperature requires a non-negligible vapour pressure. Naphthalene has intermolecular forces weak enough for this to occur slowly without heating.'],
                    ['prompt' => 'The key safety precaution when heating naphthalene is:', 'options' => ['Adding water to dilute the vapour', 'Sealing the container during heating', 'Using a strong Bunsen flame for speed', 'Keeping all open flames away because naphthalene vapour is flammable'], 'correct_option' => 3, 'explanation' => 'Naphthalene vapour ignites readily. Using an electric hotplate or water bath prevents ignition of the vapour.'],
                    ['prompt' => 'The crystals that form on the cold glass plate are:', 'options' => ['A mixture of naphthalene and any impurities present', 'Pure naphthalene', 'A compound formed by reaction with air', 'Condensed water vapour'], 'correct_option' => 1, 'explanation' => 'Only the sublimable naphthalene passes into the vapour phase. Non-volatile impurities remain in the dish.'],
                    ['prompt' => 'Compared with the original crushed flakes, crystals grown by sublimation–deposition are often:', 'options' => ['Smaller and less pure', 'Less regular in shape', 'Different in shape — often more flat and regular', 'Identical in shape to the original'], 'correct_option' => 2, 'explanation' => 'Slow crystallisation from the vapour phase allows molecules to arrange themselves into a more ordered lattice than mechanically crushed material.'],
                ],
            ],

            [
                'slug'             => 'dilution-naoh',
                'title'            => 'Preparation of Sodium Hydroxide Solution',
                'topic'            => 'Solutions',
                'difficulty'       => 'medium',
                'grade_level'      => 9,
                'prerequisite_slug'=> null,
                'chemical_equation'=> 'NaOH(s) → Na⁺(aq) + OH⁻(aq); dissolution is strongly exothermic. Dilution: C₁V₁ = C₂V₂.',
                'explanatory_note' => 'Dissolving sodium hydroxide in water releases a large amount of heat due to the high hydration enthalpy of Na⁺ and OH⁻. The solution is strongly alkaline and causes chemical burns. Goggles and gloves are mandatory. The solution must cool to room temperature before being made up to the mark in a volumetric flask.',
                'ar_available'     => false,
                'base_points'      => 60,
                'estimated_minutes'=> 25,
                'emoji'            => '⚗️',
                'card_gradient'    => 'linear-gradient(135deg,#C8F7E4,#4CAF82)',
                'steps' => [
                    ['step_order' => 1, 'title' => 'Weigh the NaOH quickly', 'instruction_text' => 'Tare a dry beaker and add the calculated mass of NaOH pellets. Weigh quickly and close the container.', 'preauthored_guidance' => 'NaOH is hygroscopic — it absorbs moisture and CO₂ from air and its mass increases on standing. Weigh rapidly and close the stock container immediately to avoid degrading the remaining material.', 'item_emoji' => '⚖️', 'action_label' => 'Weigh NaOH'],
                    ['step_order' => 2, 'title' => 'Dissolve in a small volume of distilled water', 'instruction_text' => 'Pour about 50 cm³ of distilled water into the beaker containing the NaOH. Stir until dissolved.', 'preauthored_guidance' => 'Always add alkali to water, never the reverse. Dissolution is strongly exothermic; adding a small amount of water to a large mass of solid concentrates the heat dangerously.', 'item_emoji' => '💧', 'action_label' => 'Dissolve NaOH'],
                    ['step_order' => 3, 'title' => 'Cool and transfer to a volumetric flask', 'instruction_text' => 'Once the solution has cooled to room temperature, transfer it via a funnel to a volumetric flask, rinsing the beaker three times.', 'preauthored_guidance' => 'Hot solutions have a greater volume than the same solution at room temperature. If you make up to the mark while hot, the cooled solution will be more concentrated than intended.', 'item_emoji' => '🔄', 'action_label' => 'Transfer to flask'],
                    ['step_order' => 4, 'title' => 'Make up to the graduation mark', 'instruction_text' => 'Add distilled water dropwise using a dropping pipette until the bottom of the meniscus sits exactly on the graduation mark.', 'preauthored_guidance' => 'Add the last few drops with a dropper pipette. If you overshoot the mark, the concentration is permanently wrong and the entire solution must be remade.', 'item_emoji' => '🎯', 'action_label' => 'Make up to mark'],
                ],
                'quiz' => [
                    // correct_options: [1,2,0,3]
                    ['prompt' => 'Dissolving NaOH in water releases a large amount of heat because:', 'options' => ['The reaction is endothermic', 'Hydration of the ions releases energy', 'Friction between the pellets and water generates heat', 'NaOH decomposes exothermically'], 'correct_option' => 1, 'explanation' => 'The Na⁺ and OH⁻ ions have high charge densities. When water molecules cluster around them (hydration), the energy released is large.'],
                    ['prompt' => 'The correct procedure when preparing NaOH solution is to:', 'options' => ['Add water to concentrated NaOH pellets in a flask', 'Dissolve the NaOH in a beaker, cool, then transfer to the volumetric flask', 'Add NaOH directly to the volumetric flask without pre-dissolving', 'Dissolve and make up to the mark immediately while still warm'], 'correct_option' => 1, 'explanation' => 'Pre-dissolving in a beaker and cooling before transferring prevents the hot concentrated solution from damaging the volumetric flask and avoids a volume error due to thermal expansion.'],
                    ['prompt' => 'Why must the solution cool before being made up to the mark?', 'options' => ['Hot solutions expand, giving a more concentrated solution when cool', 'To prevent the flask from cracking', 'To improve the solubility of NaOH', 'To allow impurities to settle'], 'correct_option' => 0, 'explanation' => 'A solution made up at elevated temperature has a larger volume than at room temperature. When it cools the volume decreases, making the concentration higher than intended.'],
                    ['prompt' => 'NaOH is described as hygroscopic because it:', 'options' => ['Reacts with all acids rapidly', 'Is a strong base', 'Releases heat on dissolving', 'Absorbs moisture and CO₂ from the air, changing its mass and composition'], 'correct_option' => 3, 'explanation' => 'Hygroscopic substances absorb water from the air. NaOH also reacts with CO₂ to form Na₂CO₃, which further alters the composition of the solid.'],
                ],
            ],

            [
                'slug'             => 'dilution-na2co3',
                'title'            => 'Preparation of Sodium Carbonate Standard Solution',
                'topic'            => 'Solutions',
                'difficulty'       => 'medium',
                'grade_level'      => 9,
                'prerequisite_slug'=> 'dilution-naoh',
                'chemical_equation'=> 'Na₂CO₃(s) → 2Na⁺(aq) + CO₃²⁻(aq); C₁V₁ = C₂V₂.',
                'explanatory_note' => 'Anhydrous sodium carbonate is a primary standard: it can be obtained in high purity, is stable on drying, and has a high molar mass (106 g/mol) which reduces the percentage error in weighing. It is used to prepare a solution of exactly known concentration for titration work.',
                'ar_available'     => false,
                'base_points'      => 60,
                'estimated_minutes'=> 25,
                'emoji'            => '🧪',
                'card_gradient'    => 'linear-gradient(135deg,#E8F4FD,#4FC3F7)',
                'steps' => [
                    ['step_order' => 1, 'title' => 'Dry the sodium carbonate', 'instruction_text' => 'Heat anhydrous Na₂CO₃ in an oven at 120 °C for one hour, then cool in a desiccator.', 'preauthored_guidance' => 'Any absorbed moisture increases the apparent mass, making the calculated concentration lower than the true value. The desiccator prevents re-absorption while cooling.', 'item_emoji' => '🔥', 'action_label' => 'Dry carbonate'],
                    ['step_order' => 2, 'title' => 'Weigh accurately', 'instruction_text' => 'Weigh the required mass to ±0.001 g using a four-figure balance. Transfer every grain to the beaker.', 'preauthored_guidance' => 'A primary standard solution is only as accurate as this weighing. Brush the weighing boat with a clean brush to transfer any remaining grains — every milligram matters.', 'item_emoji' => '⚖️', 'action_label' => 'Weigh accurately'],
                    ['step_order' => 3, 'title' => 'Dissolve and transfer to the flask', 'instruction_text' => 'Dissolve in 50 cm³ of distilled water, cool, and transfer to the volumetric flask, rinsing the beaker three times.', 'preauthored_guidance' => 'Na₂CO₃ dissolves with a slight warming, far less than NaOH. Cooling before transfer is still necessary for the same reason: volume accuracy at room temperature.', 'item_emoji' => '🔄', 'action_label' => 'Transfer solution'],
                    ['step_order' => 4, 'title' => 'Make up to the mark', 'instruction_text' => 'Add distilled water dropwise to exactly the graduation mark, reading the meniscus at eye level.', 'preauthored_guidance' => 'Read the meniscus with your eye level with the mark. Looking from above or below introduces parallax error — the apparent meniscus level shifts by a millimetre or more.', 'item_emoji' => '🎯', 'action_label' => 'Make up to mark'],
                ],
                'quiz' => [
                    // correct_options: [2,0,3,1]
                    ['prompt' => 'A primary standard must be obtainable in:', 'options' => ['Liquid form for easy measurement', 'Anhydrous form only, regardless of purity', 'High purity so its true mass is known', 'Pellet form for easy weighing'], 'correct_option' => 2, 'explanation' => 'A primary standard must have a precisely known composition (high purity) so that the mass weighed directly gives the moles present.'],
                    ['prompt' => 'Why is sodium carbonate dried before use as a primary standard?', 'options' => ['Absorbed moisture increases the apparent mass, giving a falsely low calculated concentration', 'To prevent it from dissolving in air', 'To make it react faster in titration', 'To remove carbonate impurities'], 'correct_option' => 0, 'explanation' => 'Water adds mass without adding carbonate. If the dried mass is not used, the calculated moles are too low and the prepared solution is more dilute than intended.'],
                    ['prompt' => 'A parallax error when reading a meniscus occurs when:', 'options' => ['The liquid is too hot', 'The flask is tilted', 'The indicator has been added too early', 'The eye is above or below the level of the graduation mark'], 'correct_option' => 3, 'explanation' => 'When the eye is not level with the mark, the line of sight cuts across the curved surface at a different point, giving a false reading.'],
                    ['prompt' => 'Na₂CO₃ is preferred over NaOH as a primary standard because:', 'options' => ['Na₂CO₃ is cheaper', 'Na₂CO₃ is more soluble', 'Na₂CO₃ is a weaker base', 'Na₂CO₃ is stable on drying, unlike NaOH which absorbs CO₂ and moisture'], 'correct_option' => 3, 'explanation' => 'NaOH absorbs CO₂ from air to form Na₂CO₃, changing its composition. Anhydrous Na₂CO₃ is stable after drying and its composition is reliably known.'],
                ],
            ],

            [
                'slug'             => 'dilution-hcl',
                'title'            => 'Preparation of Hydrochloric Acid Solution',
                'topic'            => 'Solutions',
                'difficulty'       => 'medium',
                'grade_level'      => 9,
                'prerequisite_slug'=> 'dilution-naoh',
                'chemical_equation'=> 'HCl(g) → H⁺(aq) + Cl⁻(aq); concentrated HCl ≈ 12 mol dm⁻³; C₁V₁ = C₂V₂.',
                'explanatory_note' => 'Concentrated hydrochloric acid is a fuming liquid with a corrosive, irritating vapour. All work must be done in a fume cupboard. Dilution follows the rule: acid is added to water. The C₁V₁ = C₂V₂ relationship is used to calculate the volume of concentrated acid needed.',
                'ar_available'     => false,
                'base_points'      => 65,
                'estimated_minutes'=> 25,
                'emoji'            => '💨',
                'card_gradient'    => 'linear-gradient(135deg,#FFD6C8,#FF8A50)',
                'steps' => [
                    ['step_order' => 1, 'title' => 'Calculate the required volume', 'instruction_text' => 'Use C₁V₁ = C₂V₂. Given the concentration on the bottle and your target concentration, calculate the volume of concentrated acid needed.', 'preauthored_guidance' => 'Always check the label for the exact concentration of the stock acid. Assuming a value is a common source of error in volumetric preparation.', 'item_emoji' => '🧮', 'action_label' => 'Calculate volume'],
                    ['step_order' => 2, 'title' => 'Add water to the flask first', 'instruction_text' => 'Pour the required volume of distilled water into the volumetric flask before adding the acid.', 'preauthored_guidance' => 'Adding concentrated acid to water distributes the heat of dilution through a large volume of water. Adding water to acid concentrates the heat at the bottom and can cause violent spitting.', 'item_emoji' => '💧', 'action_label' => 'Add water first'],
                    ['step_order' => 3, 'title' => 'Measure the concentrated acid', 'instruction_text' => 'In the fume cupboard, use a burette or graduated pipette with a pipette filler to measure the calculated volume of concentrated HCl.', 'preauthored_guidance' => 'Never pipette concentrated acid by mouth. The vapour causes severe irritation to the throat and lungs. Use a pipette filler or a safety bulb.', 'item_emoji' => '🧪', 'action_label' => 'Measure acid'],
                    ['step_order' => 4, 'title' => 'Add the acid and make up to the mark', 'instruction_text' => 'Pour the acid slowly into the water in the flask, mix, allow to cool, then top up to the graduation mark.', 'preauthored_guidance' => 'The flask will become warm during mixing. Allow it to cool to room temperature before making up to the mark — the same reason as with NaOH.', 'item_emoji' => '🎯', 'action_label' => 'Make up to mark'],
                ],
                'quiz' => [
                    // correct_options: [3,1,0,2]
                    ['prompt' => 'Concentrated HCl is handled in a fume cupboard because:', 'options' => ['It is very cold', 'It does not dissolve in water', 'It is a primary standard', 'Its vapour is corrosive and must not be inhaled'], 'correct_option' => 3, 'explanation' => 'HCl vapour is highly irritating to the respiratory tract and eyes. A fume cupboard removes the vapour before it reaches the worker.'],
                    ['prompt' => 'When diluting concentrated acid, the correct procedure is:', 'options' => ['Add water to the concentrated acid', 'Add the concentrated acid to water', 'Mix them simultaneously in equal portions', 'Temperature does not matter; mix in any order'], 'correct_option' => 1, 'explanation' => 'Adding acid to water dilutes the heat of mixing into a large volume of water. The reverse concentrates the heat, which can cause violent spitting of hot acid.'],
                    ['prompt' => 'C₁V₁ = C₂V₂ is used to:', 'options' => ['Calculate the volume of stock solution needed for a given dilution', 'Find the molecular mass of the acid', 'Determine the boiling point of the solution', 'Measure the electrical conductivity'], 'correct_option' => 0, 'explanation' => 'This dilution formula states that moles of solute are conserved. C₁V₁ is moles in the concentrated stock; C₂V₂ is moles in the diluted solution.'],
                    ['prompt' => 'A pipette filler is used when measuring concentrated acid because:', 'options' => ['It is more accurate than a burette', 'It speeds up the measurement', 'Concentrated acid must not be pipetted by mouth due to the corrosive vapour', 'It is made of acid-resistant material'], 'correct_option' => 2, 'explanation' => 'Mouth pipetting of any hazardous chemical is forbidden. A pipette filler applies suction mechanically, keeping the acid away from the mouth.'],
                ],
            ],

            [
                'slug'             => 'solubility-temperature',
                'title'            => 'Solubility vs Temperature Curve',
                'topic'            => 'Solutions',
                'difficulty'       => 'medium',
                'grade_level'      => 9,
                'prerequisite_slug'=> null,
                'chemical_equation'=> 'Solubility of KNO₃: ~32 g per 100 g water at 20 °C; ~109 g per 100 g water at 60 °C.',
                'explanatory_note' => 'The solubility of most ionic solids increases with temperature. A solubility curve plots grams of solute per 100 g of water against temperature. It shows the maximum amount that dissolves at each temperature and is used to design crystallisation processes.',
                'ar_available'     => false,
                'base_points'      => 65,
                'estimated_minutes'=> 25,
                'emoji'            => '📈',
                'card_gradient'    => 'linear-gradient(135deg,#FFF9C4,#FFD54F)',
                'steps' => [
                    ['step_order' => 1, 'title' => 'Prepare a near-saturated solution', 'instruction_text' => 'Weigh potassium nitrate and water accurately. Heat and stir until all the solid dissolves.', 'preauthored_guidance' => 'Record the exact masses of both solute and solvent. The entire curve depends on these values. An error here propagates through all five data points.', 'item_emoji' => '⚖️', 'action_label' => 'Weigh and dissolve'],
                    ['step_order' => 2, 'title' => 'Find the saturation temperature', 'instruction_text' => 'Allow the solution to cool with gentle stirring. Note the temperature at which the first crystal appears.', 'preauthored_guidance' => 'Stir gently — vigorous stirring can seed crystallisation prematurely with air bubbles. The temperature of the first crystal is the saturation temperature for this composition.', 'item_emoji' => '🌡️', 'action_label' => 'Find saturation point'],
                    ['step_order' => 3, 'title' => 'Repeat at different compositions', 'instruction_text' => 'Prepare solutions with different solute:solvent ratios and record the saturation temperature for each.', 'preauthored_guidance' => 'At least five data points are needed for a smooth curve. Spread them across a 30–40 °C range. Re-dissolve each sample completely before cooling.', 'item_emoji' => '🔄', 'action_label' => 'Vary composition'],
                    ['step_order' => 4, 'title' => 'Plot the solubility curve', 'instruction_text' => 'Convert each data point to grams of KNO₃ per 100 g water. Plot temperature on the x-axis and solubility on the y-axis. Draw a smooth curve.', 'preauthored_guidance' => 'Draw a smooth curve through the points, not straight lines joining them. The smooth curve represents the physical reality; straight segments imply impossible discontinuities.', 'item_emoji' => '📊', 'action_label' => 'Plot curve'],
                ],
                'quiz' => [
                    // correct_options: [1,3,2,0]
                    ['prompt' => 'The solubility of most ionic solids _____ with increasing temperature.', 'options' => ['Decreases', 'Increases', 'Stays constant', 'First increases then sharply decreases'], 'correct_option' => 1, 'explanation' => 'Higher temperature provides more energy to overcome the lattice enthalpy and allows more ions to enter solution.'],
                    ['prompt' => 'A solubility curve plots:', 'options' => ['Temperature against pressure', 'Concentration (mol dm⁻³) against time', 'Mass of solution against volume of solvent', 'Grams of solute per 100 g of water against temperature'], 'correct_option' => 3, 'explanation' => 'Solubility is expressed as mass of solute per fixed mass of solvent (100 g water) to allow comparison across different temperatures independently of the total mass.'],
                    ['prompt' => 'The temperature at which the first crystal appears on cooling gives the:', 'options' => ['Melting point of the solute', 'Boiling point of the solution', 'Saturation temperature for that composition', 'Eutectic temperature'], 'correct_option' => 2, 'explanation' => 'At the saturation temperature, the solution is exactly saturated. Any further cooling forces solute to crystallise out.'],
                    ['prompt' => 'The solubility curve is drawn as a smooth line because:', 'options' => ['Solubility changes continuously with temperature, and a smooth curve represents this reality', 'Straight lines are harder to draw accurately', 'Data points are always inaccurate', 'Smooth curves are the convention in all graphs'], 'correct_option' => 0, 'explanation' => 'Solubility is a continuous function of temperature with no sudden jumps (below any phase transitions). A smooth curve correctly represents this.'],
                ],
            ],

            [
                'slug'             => 'copper-sulphate-crystals',
                'title'            => 'Growing Copper Sulphate Crystals',
                'topic'            => 'Crystallisation',
                'difficulty'       => 'medium',
                'grade_level'      => 9,
                'prerequisite_slug'=> 'solubility-temperature',
                'chemical_equation'=> 'CuSO₄(aq) + 5H₂O(l) → CuSO₄·5H₂O(s) as the saturated solution cools.',
                'explanatory_note' => 'Copper sulphate pentahydrate crystallises from a saturated solution as the solution cools and solubility decreases. The blue colour comes from water of crystallisation around the Cu²⁺ ion. Slow cooling gives large, regular crystals; rapid cooling gives small, impure ones.',
                'ar_available'     => false,
                'base_points'      => 70,
                'estimated_minutes'=> 30,
                'emoji'            => '💎',
                'card_gradient'    => 'linear-gradient(135deg,#E8F4FD,#29B6F6)',
                'steps' => [
                    ['step_order' => 1, 'title' => 'Prepare a saturated solution', 'instruction_text' => 'Dissolve copper sulphate in hot water until no more will dissolve. Filter the hot saturated solution.', 'preauthored_guidance' => 'Filter while hot to remove undissolved solid. If you filter cold, crystals form in the filter paper and you lose product.', 'item_emoji' => '🌡️', 'action_label' => 'Saturate and filter'],
                    ['step_order' => 2, 'title' => 'Concentrate by evaporation', 'instruction_text' => 'Gently heat the filtrate in an evaporating dish, removing about one-third of the water. Stop when the surface looks slightly powdery.', 'preauthored_guidance' => 'The powdery appearance is the first sign of crystallisation. Remove the heat at this point. Continuing produces many small, impure crystals rather than a few large ones.', 'item_emoji' => '🔥', 'action_label' => 'Evaporate gently'],
                    ['step_order' => 3, 'title' => 'Allow slow, undisturbed cooling', 'instruction_text' => 'Set the evaporating dish aside at room temperature and leave undisturbed.', 'preauthored_guidance' => 'Slow cooling is the key to large crystals. Vibration or sudden chilling causes many nuclei to form at once, giving small crystals.', 'item_emoji' => '⏳', 'action_label' => 'Cool slowly'],
                    ['step_order' => 4, 'title' => 'Filter, wash and dry', 'instruction_text' => 'Filter the crystals, wash briefly with a small volume of cold saturated copper sulphate solution, and dry between sheets of filter paper.', 'preauthored_guidance' => 'Wash with saturated solution, not pure water. Pure water dissolves the surface of the crystals and reduces yield. The saturated wash removes impurities without dissolving the product.', 'item_emoji' => '💎', 'action_label' => 'Collect crystals'],
                ],
                'quiz' => [
                    // correct_options: [0,2,3,1]
                    ['prompt' => 'Copper sulphate crystals are blue because they contain:', 'options' => ['Water of crystallisation around the Cu²⁺ ion', 'An impurity of copper metal', 'Copper oxide', 'Unreacted sulfuric acid'], 'correct_option' => 0, 'explanation' => 'CuSO₄·5H₂O owes its blue colour to the five water molecules co-ordinated to each Cu²⁺ ion. Anhydrous CuSO₄ is white.'],
                    ['prompt' => 'The crystals are washed with saturated copper sulphate solution (not pure water) to:', 'options' => ['Add more copper sulphate to the crystals', 'Speed up drying', 'Avoid dissolving the crystal surfaces while removing impurities', 'Change the colour to a deeper blue'], 'correct_option' => 2, 'explanation' => 'Water would dissolve the outer layer of the crystals. A saturated wash removes surface impurities without dissolving the product because the solution is already at equilibrium with the solid.'],
                    ['prompt' => 'Slow cooling gives larger crystals because:', 'options' => ['It keeps the solution warm longer', 'It allows more evaporation to occur', 'It prevents any crystallisation', 'It gives fewer nuclei time to form, so each crystal can grow larger before space runs out'], 'correct_option' => 3, 'explanation' => 'Rapid cooling creates many nuclei simultaneously, all competing for the same dissolved copper sulphate. Slow cooling creates fewer nuclei, each of which can grow larger.'],
                    ['prompt' => 'The hot saturated solution is filtered before crystallisation to remove:', 'options' => ['Water of crystallisation', 'Undissolved impurities that would contaminate the crystals', 'Copper ions', 'Carbon dioxide'], 'correct_option' => 1, 'explanation' => 'Any solid impurity present in the hot solution would be incorporated into the growing crystals. Filtering while hot removes it before crystallisation begins.'],
                ],
            ],

            [
                'slug'             => 'solution-conductivity',
                'title'            => 'Electrical Conductivity of Solutions',
                'topic'            => 'Electrolytes',
                'difficulty'       => 'medium',
                'grade_level'      => 9,
                'prerequisite_slug'=> null,
                'chemical_equation'=> 'Strong: NaCl → Na⁺ + Cl⁻ (complete); weak: CH₃COOH ⇌ CH₃COO⁻ + H⁺ (partial); non-electrolyte: C₆H₁₂O₆ (no ions).',
                'explanatory_note' => 'A solution conducts electricity when ions are free to move. Strong electrolytes dissociate completely; weak electrolytes dissociate partially; non-electrolytes produce no ions. A simple light-bulb circuit reveals the difference by the brightness of the bulb.',
                'ar_available'     => false,
                'base_points'      => 65,
                'estimated_minutes'=> 20,
                'emoji'            => '⚡',
                'card_gradient'    => 'linear-gradient(135deg,#FFF9C4,#FFD54F)',
                'steps' => [
                    ['step_order' => 1, 'title' => 'Build the conductivity circuit', 'instruction_text' => 'Connect a battery, a light-bulb (or LED) and two carbon electrodes in series. Ensure the electrodes do not touch each other.', 'preauthored_guidance' => 'Electrodes must not touch. A short circuit lights the bulb regardless of the solution and makes every result appear to be a strong electrolyte.', 'item_emoji' => '🔌', 'action_label' => 'Build circuit'],
                    ['step_order' => 2, 'title' => 'Test distilled water', 'instruction_text' => 'Dip the electrodes in distilled water and close the circuit. Note the brightness.', 'preauthored_guidance' => 'Distilled water is a non-electrolyte. No light (or an extremely dim glow) confirms near-zero ion concentration. This is the zero baseline.', 'item_emoji' => '💧', 'action_label' => 'Test pure water'],
                    ['step_order' => 3, 'title' => 'Test each solution in turn', 'instruction_text' => 'Test NaCl, dilute HCl, dilute CH₃COOH, glucose and ethanol solutions. Rinse electrodes with distilled water between each test.', 'preauthored_guidance' => 'Rinsing prevents carry-over. If you skip the rinse, ions from the previous solution make the next one appear to conduct when it should not.', 'item_emoji' => '🧪', 'action_label' => 'Test solutions'],
                    ['step_order' => 4, 'title' => 'Classify each solution', 'instruction_text' => 'Record the brightness for each solution and classify as strong electrolyte, weak electrolyte or non-electrolyte.', 'preauthored_guidance' => 'Bright = many ions (strong electrolyte). Dim = few ions (weak electrolyte). No light = no ions (non-electrolyte). Glucose and ethanol should give no light.', 'item_emoji' => '📋', 'action_label' => 'Classify results'],
                ],
                'quiz' => [
                    // correct_options: [2,1,0,3]
                    ['prompt' => 'A solution conducts electricity because it contains:', 'options' => ['Free electrons', 'Dissolved metal atoms', 'Ions that are free to move and carry charge', 'A voltage source within the liquid'], 'correct_option' => 2, 'explanation' => 'Electrical conduction in a solution requires charge carriers. In electrolyte solutions these are ions, not free electrons (which are the carriers in metal wires).'],
                    ['prompt' => 'Ethanoic acid (CH₃COOH) is a weak electrolyte because it:', 'options' => ['Does not dissolve in water', 'Partially dissociates into ions, producing a low ion concentration', 'Is a strong acid that fully dissociates', 'Forms a precipitate with water'], 'correct_option' => 1, 'explanation' => 'Weak electrolytes establish an equilibrium between ions and the undissociated molecule. Only a fraction of the molecules are ionised, so the ion concentration is low.'],
                    ['prompt' => 'Electrodes are rinsed with distilled water between tests to:', 'options' => ['Prevent carry-over of ions from the previous solution', 'Cool the electrodes down', 'Remove any salt deposited on the carbon surface', 'Increase the conductivity of the next test'], 'correct_option' => 0, 'explanation' => 'Residual ions on the electrode surface would enter the next test solution and make a non-electrolyte appear to conduct.'],
                    ['prompt' => 'Glucose solution does not conduct electricity because glucose:', 'options' => ['Is too viscous to allow ion movement', 'Has molecules that are too large to carry charge', 'Has no electric charge on its atoms', 'Dissolves as molecules, not ions, so no charge carriers are present'], 'correct_option' => 3, 'explanation' => 'Glucose is a covalent compound that dissolves intact — its molecules carry no net charge. Without ions, there is no mechanism for electrical conduction.'],
                ],
            ],

            [
                'slug'             => 'endothermic-processes',
                'title'            => 'Endothermic Processes',
                'topic'            => 'Energy Changes',
                'difficulty'       => 'medium',
                'grade_level'      => 9,
                'prerequisite_slug'=> null,
                'chemical_equation'=> 'Ba(OH)₂·8H₂O(s) + 2NH₄SCN(s) → Ba(SCN)₂(aq) + 2NH₃(g) + 10H₂O(l); ΔH > 0.',
                'explanatory_note' => 'This strongly endothermic reaction absorbs enough heat from the surroundings to freeze a thin layer of water beneath the flask. It demonstrates that reactions can absorb rather than release energy, causing the surroundings to cool. Work in a well-ventilated area because ammonia gas is produced.',
                'ar_available'     => false,
                'base_points'      => 70,
                'estimated_minutes'=> 20,
                'emoji'            => '🧊',
                'card_gradient'    => 'linear-gradient(135deg,#E8F4FD,#4FC3F7)',
                'steps' => [
                    ['step_order' => 1, 'title' => 'Wet the wooden board', 'instruction_text' => 'Place a few drops of water on a small wooden board. The board acts as the thermal bridge between the flask and the water below.', 'preauthored_guidance' => 'A thin, even film of water is enough. Too much water takes longer to freeze; too little may not freeze at all in the short reaction time.', 'item_emoji' => '💧', 'action_label' => 'Wet board'],
                    ['step_order' => 2, 'title' => 'Place the flask and add the reagents', 'instruction_text' => 'Put the flask on the wet board. Quickly mix barium hydroxide octahydrate and ammonium thiocyanate inside the flask.', 'preauthored_guidance' => 'Work quickly and stand upwind. The reaction begins immediately on contact. Ammonia gas has a pungent smell and is an irritant. Work near fume extraction.', 'item_emoji' => '⚗️', 'action_label' => 'Mix reagents'],
                    ['step_order' => 3, 'title' => 'Stir and observe the flask cooling', 'instruction_text' => 'Stir the mixture for 30 seconds. Note the flask becoming very cold to the touch.', 'preauthored_guidance' => 'The flask can reach below −10 °C. Do not hold it bare-handed — it will stick to wet skin. Use tongs or a cloth.', 'item_emoji' => '🌡️', 'action_label' => 'Observe cooling'],
                    ['step_order' => 4, 'title' => 'Attempt to lift the board', 'instruction_text' => 'After two minutes, try to lift the flask. If the water froze, the board will be stuck to the flask base.', 'preauthored_guidance' => 'The board sticking to the flask is the observable evidence that the surroundings lost heat to the reaction. It is the most memorable demonstration of an endothermic process.', 'item_emoji' => '🏋️', 'action_label' => 'Lift board'],
                ],
                'quiz' => [
                    // correct_options: [3,0,1,2]
                    ['prompt' => 'In an endothermic reaction, energy is:', 'options' => ['Released to the surroundings, warming them', 'Unchanged — energy is conserved', 'Converted to light', 'Absorbed from the surroundings, cooling them'], 'correct_option' => 3, 'explanation' => 'An endothermic reaction has a positive ΔH. Energy flows from the surroundings into the reaction system, causing the surroundings to cool.'],
                    ['prompt' => 'The freezing of water under the flask is evidence that:', 'options' => ['The reaction absorbed heat from the surroundings (water)', 'The reaction released heat to the water', 'Water boils at a lower temperature when an acid is present', 'The flask is an insulator'], 'correct_option' => 0, 'explanation' => 'The reaction absorbs thermal energy from everything around it, including the water layer, which loses enough energy to change state and freeze.'],
                    ['prompt' => 'Ammonia gas is produced in this reaction. The appropriate safety measure is:', 'options' => ['Heating the mixture to drive off the ammonia faster', 'Working in a well-ventilated area or near fume extraction', 'Sealing the flask to contain the gas', 'Wearing heavy insulated gloves only'], 'correct_option' => 1, 'explanation' => 'Ammonia is a respiratory irritant. Ventilation prevents the gas from accumulating to harmful concentrations.'],
                    ['prompt' => 'If the temperature of the surroundings falls during a chemical process, the process is:', 'options' => ['A combustion reaction', 'An exothermic reaction', 'Endothermic — the system absorbs energy from the surroundings', 'Neutral — the change is physical, not chemical'], 'correct_option' => 2, 'explanation' => 'When the surroundings cool, they are losing energy to the system. That defines an endothermic process (ΔH > 0).'],
                ],
            ],

            // ── Class 10 ────────────────────────────────────────────────────

            [
                'slug'             => 'flame-test',
                'title'            => 'Flame Tests for Metal Ions',
                'topic'            => 'Metal Ions',
                'difficulty'       => 'medium',
                'grade_level'      => 10,
                'prerequisite_slug'=> null,
                'chemical_equation'=> 'M(s) → M*(g) → M(g) + hν; characteristic wavelengths: Li red, Na yellow, K lilac, Cu blue-green, Ca brick-red, Ba apple-green.',
                'explanatory_note' => 'Metal ions excite electrons to specific energy levels. When the electrons return to their ground state they emit photons at characteristic wavelengths seen as flame colours. Sodium contamination is common; its intense yellow can mask potassium lilac. A cobalt-blue glass filter absorbs yellow light and reveals the potassium colour.',
                'ar_available'     => true,
                'base_points'      => 70,
                'estimated_minutes'=> 20,
                'emoji'            => '🔥',
                'card_gradient'    => 'linear-gradient(135deg,#FFD6C8,#FF6D00)',
                'steps' => [
                    ['step_order' => 1, 'title' => 'Clean the wire loop', 'instruction_text' => 'Dip a nichrome wire loop in concentrated hydrochloric acid and heat to redness until no colour is produced in the flame.', 'preauthored_guidance' => 'The loop must produce a colourless flame before every test. Residual sodium from a previous test gives intense yellow that completely masks other colours.', 'item_emoji' => '🔁', 'action_label' => 'Clean wire'],
                    ['step_order' => 2, 'title' => 'Pick up a small sample', 'instruction_text' => 'Dip the clean, hot loop into a small amount of the solid salt or solution.', 'preauthored_guidance' => 'A tiny amount is sufficient. Excess solid drips into the flame and can extinguish it, preventing observation of the colour.', 'item_emoji' => '🧂', 'action_label' => 'Pick up sample'],
                    ['step_order' => 3, 'title' => 'Hold the loop in the hottest part of the flame', 'instruction_text' => 'Place the loop in the inner blue cone of the flame and observe the colour immediately.', 'preauthored_guidance' => 'Most flame colours appear in the first one or two seconds. Potassium lilac fades quickly; sodium yellow persists. Record the first colour you see clearly.', 'item_emoji' => '🔥', 'action_label' => 'Observe colour'],
                    ['step_order' => 4, 'title' => 'Record and use cobalt glass if needed', 'instruction_text' => 'Record the flame colour. If potassium is suspected in the presence of sodium, view through a cobalt-blue glass filter.', 'preauthored_guidance' => 'The cobalt glass absorbs yellow light, suppressing any sodium contamination. Through the filter, potassium lilac becomes visible even with sodium present.', 'item_emoji' => '🔵', 'action_label' => 'Record colour'],
                ],
                'quiz' => [
                    // correct_options: [0,1,3,2]
                    ['prompt' => 'The nichrome loop is cleaned with hydrochloric acid because:', 'options' => ['It removes metal ions from a previous test that would give a false colour', 'HCl makes the flame hotter', 'HCl is a colour indicator', 'It removes carbon deposits from burning'], 'correct_option' => 0, 'explanation' => 'Any residual metal ion on the wire produces its characteristic colour in the next test, masking the colour of the new sample.'],
                    ['prompt' => 'Sodium produces an intense and persistent yellow flame because:', 'options' => ['Sodium is the most reactive metal', 'It has a low ionisation energy and emits strongly at the yellow wavelength (589 nm)', 'Sodium chloride is the most common salt and gives more light', 'Yellow is the only wavelength that passes through a flame'], 'correct_option' => 1, 'explanation' => 'The sodium D-line emission at 589 nm is extremely intense due to a favourable electronic transition. Even trace amounts of sodium dominate the flame colour.'],
                    ['prompt' => 'A cobalt-blue glass filter is used when testing for potassium because it:', 'options' => ['Amplifies the lilac colour of potassium', 'Converts the potassium colour to a brighter blue', 'Increases the flame temperature', 'Absorbs yellow light from sodium contamination, making potassium lilac visible'], 'correct_option' => 3, 'explanation' => 'The cobalt glass is opaque to yellow light (Na) but transmits violet and blue light. With the yellow blocked, the lilac from potassium can be seen.'],
                    ['prompt' => 'Characteristic flame colours arise from electrons:', 'options' => ['Being stripped from the atom permanently', 'Colliding with neutral gas molecules', 'Returning from excited energy levels to lower levels and releasing photons', 'Being absorbed into the nucleus'], 'correct_option' => 2, 'explanation' => 'Heat promotes electrons to higher energy levels. When they return to lower levels, the energy difference is emitted as a photon of a specific wavelength (colour).'],
                ],
            ],

            [
                'slug'             => 'standardise-naoh',
                'title'            => 'Standardisation of NaOH by Titration',
                'topic'            => 'Volumetric Analysis',
                'difficulty'       => 'hard',
                'grade_level'      => 10,
                'prerequisite_slug'=> null,
                'chemical_equation'=> 'NaOH(aq) + HCl(aq) → NaCl(aq) + H₂O(l); at equivalence: moles NaOH = moles HCl.',
                'explanatory_note' => 'NaOH cannot be a primary standard because it absorbs CO₂ and moisture from air. Its exact concentration must be found by titration against a primary standard acid. Phenolphthalein is the indicator: colourless in acid, pale pink in alkali.',
                'ar_available'     => false,
                'base_points'      => 80,
                'estimated_minutes'=> 30,
                'emoji'            => '🔬',
                'card_gradient'    => 'linear-gradient(135deg,#FFD6C8,#FF8A50)',
                'steps' => [
                    ['step_order' => 1, 'title' => 'Fill the burette with NaOH', 'instruction_text' => 'Wash the burette twice with NaOH solution, then fill to above zero. Open the tap to fill the tip and remove air bubbles. Record the initial reading.', 'preauthored_guidance' => 'An air bubble in the tip causes a sudden jump in the volume reading when it escapes, giving a falsely large titre. Confirm no bubble is present before starting.', 'item_emoji' => '🧪', 'action_label' => 'Fill burette'],
                    ['step_order' => 2, 'title' => 'Pipette standard HCl into the flask', 'instruction_text' => 'Use a 25.00 cm³ pipette to transfer standard HCl into a conical flask. Add two drops of phenolphthalein indicator.', 'preauthored_guidance' => 'Rinse the pipette with the standard HCl before use. The acid is colourless; phenolphthalein in acid is also colourless. The first faint pink is the endpoint.', 'item_emoji' => '🔬', 'action_label' => 'Pipette acid'],
                    ['step_order' => 3, 'title' => 'Titrate to the endpoint', 'instruction_text' => 'Run NaOH from the burette while swirling continuously. Slow down as a transient pink tinge appears. The endpoint is the first permanent pale pink lasting 30 seconds.', 'preauthored_guidance' => 'Near the endpoint, add half a drop at a time — partially open the tap and touch the drop to the flask wall. A deep pink means the endpoint has been overshot.', 'item_emoji' => '🎯', 'action_label' => 'Titrate'],
                    ['step_order' => 4, 'title' => 'Repeat for concordant titres', 'instruction_text' => 'Record the titre. Repeat until two titres agree within 0.10 cm³. Average the concordant values only.', 'preauthored_guidance' => 'The first rough titre is for orientation only; do not include it in the average. Only titres agreeing within 0.10 cm³ are concordant. Including a rough titre introduces error.', 'item_emoji' => '📊', 'action_label' => 'Average concordant titres'],
                ],
                'quiz' => [
                    // correct_options: [1,3,0,2]
                    ['prompt' => 'NaOH cannot be used as a primary standard because it:', 'options' => ['Is too expensive for accurate weighing', 'Absorbs CO₂ and moisture from air, changing its concentration over time', 'Is a strong base', 'Reacts with the glass of the volumetric flask'], 'correct_option' => 1, 'explanation' => 'NaOH pellets absorb CO₂ (forming Na₂CO₃) and water from air. This changes both the mass and composition of the solid, so the weighed mass does not correspond to a known number of moles.'],
                    ['prompt' => 'The endpoint of this titration (HCl in flask, NaOH from burette) with phenolphthalein is:', 'options' => ['A permanent yellow colour', 'A permanent red colour', 'The solution turning blue', 'The first permanent pale pink colour lasting at least 30 seconds'], 'correct_option' => 3, 'explanation' => 'Phenolphthalein is colourless below pH ~8.2 and pink above. One drop of excess NaOH takes the solution past the equivalence point into the alkaline region, giving the pale pink endpoint.'],
                    ['prompt' => 'The burette is washed with NaOH solution before filling in order to:', 'options' => ['Avoid diluting the NaOH with residual water on the burette walls', 'Neutralise any acid remaining in the burette', 'Calibrate the volume of the burette', 'Lower the starting reading to zero'], 'correct_option' => 0, 'explanation' => 'Residual water dilutes the first portion of NaOH dispensed, making the effective concentration lower than intended and the titre slightly too large.'],
                    ['prompt' => 'Concordant titres are defined as those that:', 'options' => ['Give the same colour at the endpoint', 'Are performed at the same room temperature', 'Agree within 0.10 cm³ of each other', 'Use exactly the same volume of indicator'], 'correct_option' => 2, 'explanation' => 'Agreement within 0.10 cm³ shows reproducibility. Averaging concordant titres reduces the effect of random errors in individual readings.'],
                ],
            ],

            [
                'slug'             => 'standardise-hcl',
                'title'            => 'Standardisation of HCl Using NaOH',
                'topic'            => 'Volumetric Analysis',
                'difficulty'       => 'hard',
                'grade_level'      => 10,
                'prerequisite_slug'=> 'standardise-naoh',
                'chemical_equation'=> 'HCl(aq) + NaOH(aq) → NaCl(aq) + H₂O(l); moles HCl = moles NaOH at equivalence.',
                'explanatory_note' => 'Once NaOH is standardised it serves as a secondary standard to find the exact concentration of HCl. The roles of burette and flask are reversed. Methyl orange (yellow in alkali, red in acid) is a better indicator here than phenolphthalein because the endpoint at pH ~4 is more distinct in acidic conditions.',
                'ar_available'     => false,
                'base_points'      => 80,
                'estimated_minutes'=> 30,
                'emoji'            => '🧫',
                'card_gradient'    => 'linear-gradient(135deg,#C8F7E4,#2E7D57)',
                'steps' => [
                    ['step_order' => 1, 'title' => 'Fill the burette with HCl', 'instruction_text' => 'Wash and fill the burette with the HCl solution to be standardised. Record the initial reading.', 'preauthored_guidance' => 'All burette technique from the NaOH standardisation applies unchanged. The principles are the same whether acid or alkali is in the burette.', 'item_emoji' => '🧪', 'action_label' => 'Fill burette with HCl'],
                    ['step_order' => 2, 'title' => 'Pipette standardised NaOH into the flask', 'instruction_text' => 'Pipette 25.00 cm³ of the standardised NaOH into a conical flask. Add two drops of methyl orange indicator.', 'preauthored_guidance' => 'Methyl orange is yellow in alkali. As acid is added, the colour shifts toward orange, then red at the endpoint. The endpoint is the first permanent orange colour (not deep red — that is an overshoot).', 'item_emoji' => '🟡', 'action_label' => 'Pipette NaOH'],
                    ['step_order' => 3, 'title' => 'Titrate', 'instruction_text' => 'Add HCl from the burette while swirling. The yellow colour shifts to orange at the endpoint. Stop at the first permanent orange (not red).', 'preauthored_guidance' => 'The pH change is steep near the equivalence point — a single half-drop can be decisive. Add the last drops with care and compare with a colour reference if available.', 'item_emoji' => '🎯', 'action_label' => 'Titrate'],
                    ['step_order' => 4, 'title' => 'Repeat and calculate', 'instruction_text' => 'Repeat for concordant titres. Calculate the HCl concentration using n = CV and the 1:1 molar ratio.', 'preauthored_guidance' => 'An error in this HCl concentration propagates into all subsequent titrations that use it. Take the average of concordant readings carefully and carry sufficient significant figures.', 'item_emoji' => '🧮', 'action_label' => 'Calculate concentration'],
                ],
                'quiz' => [
                    // correct_options: [2,0,1,3]
                    ['prompt' => 'Methyl orange is used in this titration (acid into alkali) because:', 'options' => ['It is more accurate than phenolphthalein', 'It changes from pink to yellow at the endpoint', 'Its yellow-to-red colour change is easier to judge in the acidic endpoint region than phenolphthalein would be', 'It does not react with HCl'], 'correct_option' => 2, 'explanation' => 'Phenolphthalein changes colour between pH 8–10 (alkaline side). Methyl orange changes between pH 3–5, which is closer to the equivalence point of a strong acid–strong base titration.'],
                    ['prompt' => 'If the burette is not washed with HCl before filling, the effective concentration of HCl dispensed early in the titration is:', 'options' => ['Lower than the stock — residual water dilutes the first portion dispensed, so more volume is needed and the titre is too high', 'Higher than the stock — residual water concentrates the acid', 'Exactly equal to the stock — the volume compensates automatically', 'Unpredictably variable from run to run'], 'correct_option' => 0, 'explanation' => 'Residual water dilutes the first portion of HCl dispensed. Each cm³ contains fewer moles of acid than expected, so more volume is needed to neutralise the NaOH — giving a titre that is higher than the true value.'],
                    ['prompt' => 'HCl is standardised against NaOH rather than being prepared directly as a primary standard because:', 'options' => ['HCl is too expensive to prepare accurately', 'The exact concentration of HCl solution cannot be prepared directly — it is a gas dissolved in water and its concentration is not reliably known', 'HCl does not dissolve in water accurately', 'HCl is too weak an acid for direct standardisation'], 'correct_option' => 1, 'explanation' => 'Concentrated HCl is a solution of HCl gas in water. Its precise molarity is not reliably known from the label; it must be found by titration against a known standard.'],
                    ['prompt' => 'In this reaction, the moles of HCl at the equivalence point equal the moles of NaOH because:', 'options' => ['Both dissolve completely in water', 'Both are strong electrolytes', 'The pH is exactly 7 at the equivalence point', 'HCl and NaOH react in a 1:1 molar ratio'], 'correct_option' => 3, 'explanation' => 'HCl + NaOH → NaCl + H₂O. One mole of HCl reacts with exactly one mole of NaOH. At the equivalence point, neither is in excess.'],
                ],
            ],

            [
                'slug'             => 'molarity-na2co3',
                'title'            => 'Molarity of Sodium Carbonate by Titration',
                'topic'            => 'Volumetric Analysis',
                'difficulty'       => 'hard',
                'grade_level'      => 10,
                'prerequisite_slug'=> 'standardise-hcl',
                'chemical_equation'=> 'Na₂CO₃(aq) + 2HCl(aq) → 2NaCl(aq) + H₂O(l) + CO₂(g); molar ratio 1:2.',
                'explanatory_note' => 'Sodium carbonate reacts with HCl in a 1:2 molar ratio. Knowing the titre and the concentration of standardised HCl allows calculation of the moles and hence the molarity of the carbonate solution. Methyl orange is the indicator because the endpoint at pH ~4 corresponds to complete neutralisation of all carbonate.',
                'ar_available'     => false,
                'base_points'      => 85,
                'estimated_minutes'=> 30,
                'emoji'            => '⚗️',
                'card_gradient'    => 'linear-gradient(135deg,#C8F7E4,#4CAF82)',
                'steps' => [
                    ['step_order' => 1, 'title' => 'Prepare the Na₂CO₃ solution', 'instruction_text' => 'Dissolve an accurately weighed sample of anhydrous Na₂CO₃ in distilled water and transfer to a 250 cm³ volumetric flask. Make up to the mark.', 'preauthored_guidance' => 'Use Na₂CO₃ dried at 120 °C as in the earlier dilution experiment. Any moisture adds mass without adding carbonate moles, making the calculated concentration too low.', 'item_emoji' => '⚖️', 'action_label' => 'Prepare solution'],
                    ['step_order' => 2, 'title' => 'Pipette 25.00 cm³ into the flask', 'instruction_text' => 'Transfer 25.00 cm³ of the Na₂CO₃ solution to a conical flask. Add two drops of methyl orange.', 'preauthored_guidance' => 'Methyl orange changes at the second equivalence point of Na₂CO₃ + HCl — the point where all carbonate has been converted to CO₂ and NaCl. This is the complete neutralisation endpoint.', 'item_emoji' => '🧪', 'action_label' => 'Pipette carbonate'],
                    ['step_order' => 3, 'title' => 'Titrate with standardised HCl', 'instruction_text' => 'Run standardised HCl from the burette. Titrate to the first permanent orange-to-red colour change.', 'preauthored_guidance' => 'The endpoint colour change can be gradual here. Add the last portion drop by drop and compare with a reference colour. An overshoot to deep red means the endpoint was missed.', 'item_emoji' => '🎯', 'action_label' => 'Titrate'],
                    ['step_order' => 4, 'title' => 'Calculate the molarity', 'instruction_text' => 'Using Na₂CO₃ : HCl = 1 : 2, find the moles of Na₂CO₃ from the titre and hence the molarity.', 'preauthored_guidance' => 'Cross-check: the molarity from the titration should agree with the molarity calculated from the mass you weighed at the start. A large discrepancy means an error in one of the two calculations.', 'item_emoji' => '🧮', 'action_label' => 'Calculate molarity'],
                ],
                'quiz' => [
                    // correct_options: [3,2,0,1]
                    ['prompt' => 'The molar ratio of Na₂CO₃ to HCl in this reaction is:', 'options' => ['1:1', '2:1 (Na₂CO₃ : HCl)', '1:3', '1:2 (Na₂CO₃ : HCl)'], 'correct_option' => 3, 'explanation' => 'Na₂CO₃ has two ionisable protons (as carbonate CO₃²⁻ accepts two H⁺). One mole of Na₂CO₃ therefore requires exactly two moles of HCl for complete neutralisation.'],
                    ['prompt' => 'Methyl orange is used in this titration rather than phenolphthalein because:', 'options' => ['It is less expensive', 'It is more sensitive to colour changes in general', 'It changes colour at a pH that corresponds to complete neutralisation of the carbonate to CO₂', 'It reacts with carbonate to give a coloured product'], 'correct_option' => 2, 'explanation' => 'Na₂CO₃ reacts with HCl in two stages. Phenolphthalein changes at the first stage (carbonate → bicarbonate). Methyl orange changes at the second stage (complete neutralisation), which is what this experiment requires.'],
                    ['prompt' => 'If Na₂CO₃ is not dried before weighing, the measured molarity will be:', 'options' => ['Lower than the true value, because moisture adds mass without adding carbonate moles', 'Higher than the true value', 'Unaffected by a small amount of moisture', 'Double the true value'], 'correct_option' => 0, 'explanation' => 'Water increases the apparent mass. The calculated moles (mass ÷ molar mass of Na₂CO₃) will be too high, but the actual carbonate in the flask is less — giving a solution more dilute than intended.'],
                    ['prompt' => 'The titre volume is used together with the HCl concentration to find:', 'options' => ['The mass of Na₂CO₃ used', 'The moles of HCl used, which give the moles of Na₂CO₃ via the stoichiometric ratio', 'The density of the solution', 'The solubility of Na₂CO₃ at room temperature'], 'correct_option' => 1, 'explanation' => 'Moles HCl = concentration × titre volume. Moles Na₂CO₃ = moles HCl ÷ 2 (from the 1:2 ratio). Molarity Na₂CO₃ = moles ÷ volume of pipette aliquot.'],
                ],
            ],

            [
                'slug'             => 'molarity-oxalic-acid',
                'title'            => 'Molarity of Oxalic Acid by Titration',
                'topic'            => 'Volumetric Analysis',
                'difficulty'       => 'hard',
                'grade_level'      => 10,
                'prerequisite_slug'=> 'standardise-naoh',
                'chemical_equation'=> 'H₂C₂O₄(aq) + 2NaOH(aq) → Na₂C₂O₄(aq) + 2H₂O(l); molar ratio 1:2.',
                'explanatory_note' => 'Oxalic acid (ethanedioic acid) is a diprotic acid — it donates two protons. Its molar ratio with NaOH is 1:2. Oxalic acid dihydrate is itself a primary standard, so this experiment can verify the NaOH standardisation independently. Phenolphthalein is the indicator because the equivalence point is on the alkaline side.',
                'ar_available'     => false,
                'base_points'      => 85,
                'estimated_minutes'=> 30,
                'emoji'            => '🧮',
                'card_gradient'    => 'linear-gradient(135deg,#FFF9C4,#FFD54F)',
                'steps' => [
                    ['step_order' => 1, 'title' => 'Prepare the oxalic acid solution', 'instruction_text' => 'Accurately weigh oxalic acid dihydrate (H₂C₂O₄·2H₂O). Dissolve in distilled water and make up to 250 cm³ in a volumetric flask.', 'preauthored_guidance' => 'Use the dihydrate formula mass (126.07 g/mol), not the anhydrous value (90.03 g/mol). Confusing the two is the most common error with this primary standard.', 'item_emoji' => '⚖️', 'action_label' => 'Prepare oxalic acid'],
                    ['step_order' => 2, 'title' => 'Pipette 25.00 cm³ into the flask', 'instruction_text' => 'Transfer 25.00 cm³ of the oxalic acid solution to a conical flask. Add two drops of phenolphthalein indicator.', 'preauthored_guidance' => 'Phenolphthalein is colourless in acid. The solution remains colourless until the equivalence point is passed and excess NaOH makes it alkaline.', 'item_emoji' => '🔬', 'action_label' => 'Pipette acid'],
                    ['step_order' => 3, 'title' => 'Titrate with standardised NaOH', 'instruction_text' => 'Run NaOH from the burette. The endpoint is the first permanent pale pink.', 'preauthored_guidance' => 'Oxalic acid is a moderately weak acid. The pH change at the equivalence point is less steep than for HCl. Slow, careful addition near the endpoint is even more important.', 'item_emoji' => '🎯', 'action_label' => 'Titrate'],
                    ['step_order' => 4, 'title' => 'Calculate and verify', 'instruction_text' => 'Calculate the molarity of NaOH from the titre. Compare with the value from the HCl standardisation.', 'preauthored_guidance' => 'Good agreement (within 0.2%) confirms both standardisations are correct. A large discrepancy means one procedure has an error — repeat both before proceeding.', 'item_emoji' => '📊', 'action_label' => 'Verify NaOH molarity'],
                ],
                'quiz' => [
                    // correct_options: [0,1,2,3]
                    ['prompt' => 'Oxalic acid reacts with NaOH in a molar ratio of:', 'options' => ['1:2 (oxalic acid : NaOH)', '1:1', '2:1 (oxalic acid : NaOH)', '1:3'], 'correct_option' => 0, 'explanation' => 'Oxalic acid is diprotic (H₂C₂O₄). Each molecule donates two H⁺ ions. One mole of oxalic acid therefore reacts with two moles of NaOH.'],
                    ['prompt' => 'The correct formula mass to use when preparing oxalic acid solution is:', 'options' => ['90.03 g/mol (anhydrous H₂C₂O₄)', '126.07 g/mol (dihydrate H₂C₂O₄·2H₂O)', '176.12 g/mol', '63.03 g/mol (half the molar mass)'], 'correct_option' => 1, 'explanation' => 'Oxalic acid is stored and sold as the dihydrate (two water molecules per formula unit). The dihydrate mass must be used to calculate the correct number of moles from the mass weighed.'],
                    ['prompt' => 'Phenolphthalein is used as indicator in this titration because:', 'options' => ['It is cheaper than methyl orange', 'It reacts with oxalic acid to give a colour before the endpoint', 'The equivalence point is in the alkaline region where phenolphthalein changes colour', 'It is more accurate than methyl orange for all titrations'], 'correct_option' => 2, 'explanation' => 'The salt formed (sodium oxalate) is slightly alkaline. The equivalence point is above pH 7, which is in the range where phenolphthalein changes colour (pH 8–10).'],
                    ['prompt' => 'This experiment can independently verify the NaOH standardisation because:', 'options' => ['It uses the same glassware', 'It uses the same indicator', 'It uses the same volume of NaOH', 'Oxalic acid dihydrate is a primary standard with an independently known concentration'], 'correct_option' => 3, 'explanation' => 'A primary standard has an exactly known composition. By preparing oxalic acid from a precisely weighed mass, its concentration is known without needing a prior standardisation.'],
                ],
            ],

            [
                'slug'             => 'weak-acids-natural',
                'title'            => 'Weak Acids in Foods',
                'topic'            => 'Acids and Bases',
                'difficulty'       => 'easy',
                'grade_level'      => 10,
                'prerequisite_slug'=> null,
                'chemical_equation'=> 'CH₃COOH(aq) ⇌ CH₃COO⁻(aq) + H⁺(aq) (partial); citric acid: C₆H₈O₇; tartaric acid: C₄H₆O₆.',
                'explanatory_note' => 'Weak acids are found in many foods: acetic acid in vinegar, citric acid in citrus fruits, tartaric acid in tamarinds. They partially dissociate in water, producing a lower [H⁺] than a strong acid of the same concentration. This experiment tests pH and reactivity of weak and strong acids at equal concentration.',
                'ar_available'     => false,
                'base_points'      => 50,
                'estimated_minutes'=> 20,
                'emoji'            => '🍋',
                'card_gradient'    => 'linear-gradient(135deg,#FFF9C4,#FFD54F)',
                'steps' => [
                    ['step_order' => 1, 'title' => 'Prepare equal-concentration solutions', 'instruction_text' => 'Prepare 0.1 mol dm⁻³ solutions of ethanoic acid, citric acid and hydrochloric acid.', 'preauthored_guidance' => 'All three must have exactly the same concentration. Any difference means pH differences are due to concentration, not degree of dissociation — which defeats the purpose of the comparison.', 'item_emoji' => '🧪', 'action_label' => 'Prepare solutions'],
                    ['step_order' => 2, 'title' => 'Measure the pH of each', 'instruction_text' => 'Use universal indicator paper or a calibrated pH meter. Record the pH of each solution.', 'preauthored_guidance' => 'Rinse the pH electrode with distilled water between readings. If using indicator paper, use a fresh strip for each solution to avoid carry-over.', 'item_emoji' => '🌡️', 'action_label' => 'Measure pH'],
                    ['step_order' => 3, 'title' => 'Add magnesium ribbon to each', 'instruction_text' => 'Add a short piece of magnesium ribbon to each test tube. Observe and compare the rate of fizzing.', 'preauthored_guidance' => 'Rate of gas evolution depends on [H⁺]. The weak acids produce fewer H⁺ ions and react more slowly despite having the same total concentration as the HCl.', 'item_emoji' => '🔥', 'action_label' => 'React with magnesium'],
                    ['step_order' => 4, 'title' => 'Rank and explain', 'instruction_text' => 'Rank the acids by pH and by rate of magnesium reaction. Relate the ranking to degree of dissociation.', 'preauthored_guidance' => 'HCl should have the lowest pH (fully dissociated, most H⁺). The weak acids have higher pH (partially dissociated, fewer H⁺). Both rankings should be consistent.', 'item_emoji' => '📋', 'action_label' => 'Rank and conclude'],
                ],
                'quiz' => [
                    // correct_options: [1,2,3,0]
                    ['prompt' => 'At the same concentration, a weak acid has a higher pH than a strong acid because:', 'options' => ['The weak acid has fewer molecules', 'The weak acid only partially dissociates, producing fewer H⁺ ions per mole', 'The weak acid is less dense', 'The strong acid reacts with water more slowly'], 'correct_option' => 1, 'explanation' => 'pH depends on [H⁺]. A weak acid establishes an equilibrium where only a fraction of molecules donate a proton, so [H⁺] is lower and pH is higher than for a fully dissociated strong acid.'],
                    ['prompt' => 'Ethanoic acid in vinegar is an example of:', 'options' => ['A strong inorganic acid', 'An inorganic acid', 'A weak acid found naturally in food', 'A base disguised as an acid'], 'correct_option' => 2, 'explanation' => 'Ethanoic acid (acetic acid) is an organic weak acid. It partially dissociates in water and is the acid responsible for the sour taste of vinegar.'],
                    ['prompt' => 'Magnesium reacts faster with HCl than with ethanoic acid at the same concentration because:', 'options' => ['Magnesium is more reactive with chlorides', 'HCl is more dense', 'Magnesium reacts with Cl⁻ ions directly', 'HCl is fully dissociated, providing a higher [H⁺] per mole than the weak acid'], 'correct_option' => 3, 'explanation' => 'The rate of reaction with a metal depends on [H⁺]. Fully dissociated HCl provides more H⁺ ions per mole than partially dissociated ethanoic acid at the same concentration.'],
                    ['prompt' => 'Universal indicator paper estimates pH by:', 'options' => ['Changing colour according to the hydrogen ion concentration', 'Absorbing acid molecules', 'Reacting with the solvent to produce a gas', 'Measuring electrical conductance'], 'correct_option' => 0, 'explanation' => 'Universal indicator is a mixture of dyes whose combined colour varies continuously with pH. Comparing the colour with a reference chart gives an estimated pH value.'],
                ],
            ],

            [
                'slug'             => 'acid-base-neutral',
                'title'            => 'Acids, Bases and Neutralisation',
                'topic'            => 'Acids and Bases',
                'difficulty'       => 'easy',
                'grade_level'      => 10,
                'prerequisite_slug'=> null,
                'chemical_equation'=> 'HCl(aq) + NaOH(aq) → NaCl(aq) + H₂O(l); H₂SO₄ + 2NaOH → Na₂SO₄ + 2H₂O; CH₃COOH + NaOH → CH₃COONa + H₂O.',
                'explanatory_note' => 'Neutralisation is the reaction of an acid with a base to form a salt and water. pH below 7 is acidic, pH 7 is neutral, and pH above 7 is alkaline at 25 °C. This experiment classifies solutions using universal indicator and confirms the classification with chemical tests, then performs and confirms neutralisation.',
                'ar_available'     => false,
                'base_points'      => 50,
                'estimated_minutes'=> 20,
                'emoji'            => '⚖️',
                'card_gradient'    => 'linear-gradient(135deg,#E8F4FD,#4FC3F7)',
                'steps' => [
                    ['step_order' => 1, 'title' => 'Prepare the three test solutions', 'instruction_text' => 'Label test tubes of dilute HCl, dilute NaOH and sodium chloride solution.', 'preauthored_guidance' => 'Use similar concentrations for HCl and NaOH so the comparison is fair. NaCl solution represents a neutral solution — the product of a strong acid + strong base neutralisation.', 'item_emoji' => '🏷️', 'action_label' => 'Label solutions'],
                    ['step_order' => 2, 'title' => 'Add universal indicator', 'instruction_text' => 'Add 2–3 drops of universal indicator to each test tube. Record the colours and estimate the pH.', 'preauthored_guidance' => 'Use 2–3 drops only. Adding too much indicator can slightly change the pH of the solution. Record the colour immediately — some indicators fade over time.', 'item_emoji' => '🌈', 'action_label' => 'Test with indicator'],
                    ['step_order' => 3, 'title' => 'Test with magnesium ribbon', 'instruction_text' => 'Add a short piece of magnesium to each solution. Note which solutions produce hydrogen and which do not.', 'preauthored_guidance' => 'Acids react with magnesium to produce hydrogen. Alkalis and neutral solutions do not react. This is a confirmatory test that backs up the indicator result.', 'item_emoji' => '🔬', 'action_label' => 'React with Mg'],
                    ['step_order' => 4, 'title' => 'Neutralise the acid and confirm', 'instruction_text' => 'Add NaOH drop by drop to the HCl until the indicator turns green. Confirm neutralisation with pH paper.', 'preauthored_guidance' => 'Add drop by drop — overshooting produces an alkaline solution, not a neutral one. The indicator colour passes through green (neutral) quickly. Stop at green.', 'item_emoji' => '🎯', 'action_label' => 'Neutralise acid'],
                ],
                'quiz' => [
                    // correct_options: [2,3,1,0]
                    ['prompt' => 'At 25 °C, a neutral aqueous solution has a pH of:', 'options' => ['Less than 7', 'Greater than 7', 'Exactly 7', 'Less than 14 but not necessarily 7'], 'correct_option' => 2, 'explanation' => 'Neutrality means [H⁺] = [OH⁻] = 10⁻⁷ mol dm⁻³ at 25 °C, which gives pH = 7.'],
                    ['prompt' => 'Magnesium reacts with acids but not with alkalis because:', 'options' => ['Magnesium dissolves only in pure water', 'Alkalis have no reactive OH⁻ groups', 'Magnesium is a noble metal', 'The reaction requires H⁺ ions, which are present in significant concentration in acids but not in alkalis'], 'correct_option' => 3, 'explanation' => 'Mg + 2H⁺ → Mg²⁺ + H₂. The reaction requires H⁺ ions as the oxidising agent. Alkalis have low [H⁺] (they are alkaline because [OH⁻] > [H⁺]) so no significant reaction occurs.'],
                    ['prompt' => 'In the neutralisation of HCl with NaOH, the products are:', 'options' => ['H₂ gas and Cl₂ gas', 'NaCl and water', 'NaOH and HCl in equilibrium', 'Na₂O and HCl'], 'correct_option' => 1, 'explanation' => 'H⁺(aq) + OH⁻(aq) → H₂O(l). The spectator ions Na⁺ and Cl⁻ remain in solution and form sodium chloride (NaCl) when the water is evaporated.'],
                    ['prompt' => 'Adding too much NaOH when trying to neutralise HCl results in:', 'options' => ['An alkaline solution — the excess NaOH raises the pH above 7', 'No effect once all the HCl is consumed', 'Production of hydrogen gas', 'Self-neutralisation of the excess NaOH'], 'correct_option' => 0, 'explanation' => 'Once all the H⁺ has been neutralised, any additional NaOH provides OH⁻ ions that are not consumed, raising the pH above 7 and making the solution alkaline.'],
                ],
            ],

            [
                'slug'             => 'aldehydes-fehling-tollens',
                'title'            => 'Identification of Aldehydes: Fehling\'s and Tollens\' Tests',
                'topic'            => 'Organic Chemistry',
                'difficulty'       => 'medium',
                'grade_level'      => 10,
                'prerequisite_slug'=> 'weak-acids-natural',
                'chemical_equation'=> 'Fehling\'s: RCHO + 2Cu²⁺ + 5OH⁻ → RCOO⁻ + Cu₂O(s) + 3H₂O (brick-red). Tollens\': RCHO + 2[Ag(NH₃)₂]⁺ + 2OH⁻ → RCOO⁻ + 2Ag(s) + 4NH₃ + H₂O (silver mirror).',
                'explanatory_note' => 'Aldehydes are reducing agents: they reduce Cu²⁺ to Cu⁺ (Fehling\'s, brick-red Cu₂O precipitate) and reduce Ag⁺ to Ag metal (Tollens\', silver mirror). Ketones are not reducing agents under these mild conditions and give negative results in both tests.',
                'ar_available'     => false,
                'base_points'      => 70,
                'estimated_minutes'=> 25,
                'emoji'            => '🟤',
                'card_gradient'    => 'linear-gradient(135deg,#FFD6C8,#FF8A50)',
                'steps' => [
                    ['step_order' => 1, 'title' => 'Prepare Fehling\'s solution', 'instruction_text' => 'Mix equal volumes of Fehling\'s A (copper sulphate solution) and Fehling\'s B (sodium potassium tartrate in NaOH) in a test tube.', 'preauthored_guidance' => 'Prepare Fehling\'s reagent fresh. It decomposes on standing and overheating without a substrate produces a black precipitate that looks like a false positive.', 'item_emoji' => '💙', 'action_label' => 'Prepare Fehling\'s'],
                    ['step_order' => 2, 'title' => 'Test with aldehyde and ketone', 'instruction_text' => 'Add a few drops of ethanal (aldehyde) to one tube of Fehling\'s and a few drops of propanone (ketone) to another. Heat both in a water bath at 60 °C.', 'preauthored_guidance' => 'Use a water bath, not a direct flame. Overheating Fehling\'s without substrate gives a black copper oxide precipitate — a false positive. The ethanal tube should give a brick-red precipitate; propanone should give no change.', 'item_emoji' => '🌡️', 'action_label' => 'Test compounds'],
                    ['step_order' => 3, 'title' => 'Observe and record', 'instruction_text' => 'Note brick-red precipitate (positive) or no change (negative) for each compound.', 'preauthored_guidance' => 'A dull brown precipitate is weakly positive. An unmistakable brick-red deposit of Cu₂O is the unambiguous positive result.', 'item_emoji' => '📋', 'action_label' => 'Record results'],
                    ['step_order' => 4, 'title' => 'Repeat with Tollens\' reagent', 'instruction_text' => 'Prepare fresh Tollens\' reagent. Test both compounds in a warm water bath. Look for a silver mirror forming on the tube wall.', 'preauthored_guidance' => 'Use Tollens\' reagent immediately and discard with water after the experiment. Old Tollens\' reagent can form explosive silver nitride (Ag₃N). Never heat Tollens\' reagent to dryness.', 'item_emoji' => '🪞', 'action_label' => 'Test with Tollens\''],
                ],
                'quiz' => [
                    // correct_options: [3,0,2,1]
                    ['prompt' => 'In Fehling\'s test, the brick-red precipitate is:', 'options' => ['Copper metal (Cu)', 'Copper(II) oxide (CuO)', 'Copper(II) hydroxide (Cu(OH)₂)', 'Copper(I) oxide (Cu₂O)'], 'correct_option' => 3, 'explanation' => 'Cu²⁺ is reduced to Cu⁺ by the aldehyde. Cu⁺ then forms Cu₂O, which is insoluble and precipitates as the characteristic brick-red solid.'],
                    ['prompt' => 'Ketones give a negative Fehling\'s test because they:', 'options' => ['Are not reducing agents under the mild conditions of the Fehling\'s test', 'Do not dissolve in Fehling\'s solution', 'React to give a blue precipitate instead', 'Are too large to diffuse to the Cu²⁺ ions'], 'correct_option' => 0, 'explanation' => 'Ketones lack the aldehyde C–H bond adjacent to the carbonyl, which is what allows aldehydes to act as reducing agents. Ketones have no equivalent easily-oxidised bond.'],
                    ['prompt' => 'Why must Tollens\' reagent be prepared fresh and discarded immediately after use?', 'options' => ['It loses its ammonia on standing', 'It reacts with glass on storage', 'Old reagent can form explosive silver nitride (Ag₃N)', 'It is too expensive to store'], 'correct_option' => 2, 'explanation' => 'Silver nitride forms from the reaction of silver with ammonia over time. It is shock-sensitive and potentially explosive. All Tollens\' reagent must be discarded — never evaporated to dryness.'],
                    ['prompt' => 'The silver mirror in Tollens\' test forms because:', 'options' => ['The ketone reduces silver ions to silver metal', 'The aldehyde reduces Ag⁺ to Ag metal, which deposits on the glass wall of the tube', 'Silver oxide decomposes on warming the glass', 'Ammonia reacts with silver to produce a mirror coating'], 'correct_option' => 1, 'explanation' => 'RCHO + 2[Ag(NH₃)₂]⁺ → RCOO⁻ + 2Ag + 4NH₃. The aldehyde is oxidised; Ag⁺ is reduced to Ag metal. The metal deposits as a reflective layer on the clean glass surface.'],
                ],
            ],

            [
                'slug'             => 'ketones-dnph',
                'title'            => 'Identification of Ketones: Brady\'s Reagent (2,4-DNPH)',
                'topic'            => 'Organic Chemistry',
                'difficulty'       => 'medium',
                'grade_level'      => 10,
                'prerequisite_slug'=> 'aldehydes-fehling-tollens',
                'chemical_equation'=> 'R-CO-R\' + 2,4-DNPH → (R-CO-R\')·DNPH (orange-yellow precipitate); aldehydes also react. 2,4-DNPH tests for C=O; Fehling\'s then distinguishes aldehyde from ketone.',
                'explanatory_note' => '2,4-Dinitrophenylhydrazine (Brady\'s reagent) forms a bright orange or yellow crystalline precipitate with any compound containing a C=O (carbonyl) group — both aldehydes and ketones. It does not react with alcohols, acids or esters. The melting point of the derivative identifies the specific compound.',
                'ar_available'     => false,
                'base_points'      => 70,
                'estimated_minutes'=> 25,
                'emoji'            => '🟡',
                'card_gradient'    => 'linear-gradient(135deg,#FFF9C4,#FFD54F)',
                'steps' => [
                    ['step_order' => 1, 'title' => 'Add 2,4-DNPH to four samples', 'instruction_text' => 'Add 2 cm³ of 2,4-DNPH reagent to each of four test tubes containing ethanal, propanone, ethanol and ethanoic acid.', 'preauthored_guidance' => '2,4-DNPH reagent is dissolved in acidified methanol and is highly toxic. Handle in a fume cupboard. Use separate test tubes to keep the results independent.', 'item_emoji' => '🧪', 'action_label' => 'Add Brady\'s reagent'],
                    ['step_order' => 2, 'title' => 'Observe for precipitate', 'instruction_text' => 'Note which tubes produce an orange-yellow precipitate. Record immediately.', 'preauthored_guidance' => 'The precipitate forms within seconds for reactive carbonyls (ethanal, propanone). Ethanol and ethanoic acid should give no precipitate — they have no reactive C=O group.', 'item_emoji' => '🔍', 'action_label' => 'Observe precipitate'],
                    ['step_order' => 3, 'title' => 'Filter the derivative', 'instruction_text' => 'Filter the precipitate from a positive result. Wash with cold ethanol and allow to dry.', 'preauthored_guidance' => 'The pure, dry derivative has a sharp characteristic melting point — one of the classic methods for identifying an unknown carbonyl compound.', 'item_emoji' => '🔵', 'action_label' => 'Isolate derivative'],
                    ['step_order' => 4, 'title' => 'Compare results with Fehling\'s', 'instruction_text' => 'From your records: compounds positive for 2,4-DNPH but negative for Fehling\'s are ketones. Those positive for both are aldehydes.', 'preauthored_guidance' => 'This two-step identification is systematic. 2,4-DNPH says "carbonyl present"; Fehling\'s says "aldehyde present". Positive for one only = ketone. Positive for both = aldehyde.', 'item_emoji' => '📊', 'action_label' => 'Classify compounds'],
                ],
                'quiz' => [
                    // correct_options: [0,2,1,3]
                    ['prompt' => 'Brady\'s reagent (2,4-DNPH) tests for the presence of:', 'options' => ['A carbonyl (C=O) group in aldehydes and ketones', 'A hydroxyl (OH) group in alcohols', 'A carboxyl (COOH) group in acids', 'A carbon–carbon double bond in alkenes'], 'correct_option' => 0, 'explanation' => 'The hydrazine group in 2,4-DNPH undergoes condensation with the C=O group. This reaction is specific to aldehydes and ketones.'],
                    ['prompt' => 'Ethanol gives a negative 2,4-DNPH result because:', 'options' => ['Ethanol is too small to react', 'Ethanol reacts to give a colourless, invisible product', 'Ethanol is an alcohol and has no C=O carbonyl group to react with 2,4-DNPH', 'Ethanol is a gas at room temperature'], 'correct_option' => 2, 'explanation' => 'Brady\'s reagent is specific for the carbonyl group. Ethanol (CH₃CH₂OH) has only an O–H group, not a C=O, so it does not react.'],
                    ['prompt' => 'The colour of the 2,4-DNPH derivative precipitate is:', 'options' => ['Blue', 'Orange or yellow', 'Green', 'Colourless'], 'correct_option' => 1, 'explanation' => 'The dinitrophenylhydrazone derivatives are strongly coloured yellow to orange due to the extended conjugated system containing two nitro groups.'],
                    ['prompt' => 'To distinguish an aldehyde from a ketone after a positive Brady\'s test, the next step is:', 'options' => ['Use universal indicator', 'Use litmus paper', 'Use silver nitrate solution', 'Use Fehling\'s test — aldehydes give brick-red Cu₂O, ketones do not'], 'correct_option' => 3, 'explanation' => '2,4-DNPH is not selective between aldehydes and ketones. Fehling\'s or Tollens\' test provides the second stage of identification: only aldehydes give a positive result.'],
                ],
            ],

            [
                'slug'             => 'carboxylic-acids-test',
                'title'            => 'Identification Tests for Carboxylic Acids',
                'topic'            => 'Organic Chemistry',
                'difficulty'       => 'medium',
                'grade_level'      => 10,
                'prerequisite_slug'=> 'weak-acids-natural',
                'chemical_equation'=> 'CH₃COOH + NaHCO₃ → CH₃COONa + H₂O + CO₂(g); esterification: CH₃COOH + C₂H₅OH ⇌ CH₃COOC₂H₅ + H₂O (conc. H₂SO₄ catalyst).',
                'explanatory_note' => 'Carboxylic acids are identified by their reaction with carbonates (CO₂ gas, confirmed with limewater) and by ester formation with an alcohol in the presence of concentrated sulfuric acid. They do not react with 2,4-DNPH, distinguishing them from aldehydes and ketones. Reaction with NaHCO₃ distinguishes them from phenols.',
                'ar_available'     => false,
                'base_points'      => 70,
                'estimated_minutes'=> 25,
                'emoji'            => '🫙',
                'card_gradient'    => 'linear-gradient(135deg,#C8F7E4,#4CAF82)',
                'steps' => [
                    ['step_order' => 1, 'title' => 'Test with sodium carbonate', 'instruction_text' => 'Add a spatula of anhydrous Na₂CO₃ to ethanoic acid. Hold damp limewater paper over the tube to test the gas.', 'preauthored_guidance' => 'Effervescence confirms an acid; limewater turning milky confirms CO₂. This combination rules out fizzing from other sources such as dissolved gas.', 'item_emoji' => '🧪', 'action_label' => 'Test with Na₂CO₃'],
                    ['step_order' => 2, 'title' => 'Test with sodium hydrogencarbonate', 'instruction_text' => 'Repeat with NaHCO₃ instead of Na₂CO₃. Confirm CO₂ with limewater.', 'preauthored_guidance' => 'Carboxylic acids react with NaHCO₃ (liberating CO₂) but phenols do not. This is the key test distinguishing carboxylic acids from phenols — both are acidic but only carboxylic acids are strong enough to react with NaHCO₃.', 'item_emoji' => '💡', 'action_label' => 'Test with NaHCO₃'],
                    ['step_order' => 3, 'title' => 'Test with 2,4-DNPH', 'instruction_text' => 'Add Brady\'s reagent to ethanoic acid. A carboxylic acid should give no precipitate.', 'preauthored_guidance' => 'No precipitate confirms there is no reactive carbonyl C=O group in the sense tested by 2,4-DNPH. Carboxylic acids have a C=O in the COOH group but it is not reactive with Brady\'s reagent under normal conditions.', 'item_emoji' => '🚫', 'action_label' => 'Brady\'s test (negative)'],
                    ['step_order' => 4, 'title' => 'Esterification test', 'instruction_text' => 'Heat ethanoic acid with ethanol and a few drops of concentrated H₂SO₄. Pour the product into water. Note the fruity smell.', 'preauthored_guidance' => 'Concentrated H₂SO₄ is the catalyst and dehydrating agent. It is highly corrosive — add it carefully with a dropper. The fruity smell of ethyl ethanoate is diagnostic of ester formation, which is characteristic of carboxylic acids.', 'item_emoji' => '🍎', 'action_label' => 'Esterification test'],
                ],
                'quiz' => [
                    // correct_options: [1,3,0,2]
                    ['prompt' => 'The gas produced when carboxylic acid reacts with sodium carbonate is confirmed by:', 'options' => ['Universal indicator turning red', 'Limewater turning milky (CO₂ converts Ca(OH)₂ to CaCO₃)', 'A squeaky pop (confirming H₂)', 'A lit splint reigniting (confirming O₂)'], 'correct_option' => 1, 'explanation' => 'CO₂ + Ca(OH)₂ → CaCO₃ + H₂O. The white precipitate of CaCO₃ makes the limewater appear milky — the standard test for CO₂.'],
                    ['prompt' => 'Carboxylic acids react with NaHCO₃ but phenols do not, because:', 'options' => ['Phenols are insoluble in water', 'Carboxylic acids are colourless liquids while phenols are solids', 'Phenols do not have an acidic OH group', 'Carboxylic acids are stronger acids than phenols and can protonate the carbonate ion to release CO₂'], 'correct_option' => 3, 'explanation' => 'Phenols have a pKa of ~10; HCO₃⁻ has a conjugate acid (H₂CO₃) with a pKa of ~6.4. Since phenol is a weaker acid than carbonic acid, it cannot displace CO₂ from bicarbonate.'],
                    ['prompt' => 'The fruity smell in the esterification test confirms formation of:', 'options' => ['An ester (ethyl ethanoate)', 'An alcohol (excess ethanol evaporating)', 'A ketone', 'An aldehyde'], 'correct_option' => 0, 'explanation' => 'CH₃COOH + C₂H₅OH → CH₃COOC₂H₅ + H₂O. Ethyl ethanoate has a characteristic sweet, fruity smell. Ester formation is diagnostic of the carboxylic acid functional group.'],
                    ['prompt' => 'Concentrated H₂SO₄ is used in esterification as:', 'options' => ['A reactant that forms part of the ester', 'A base to neutralise the acid', 'Both a catalyst and a dehydrating agent that drives the equilibrium toward ester formation', 'A solvent for the reaction'], 'correct_option' => 2, 'explanation' => 'H₂SO₄ catalyses the reaction by protonating the carbonyl group. It also absorbs the water produced, shifting the equilibrium toward ester formation (Le Chatelier\'s principle).'],
                ],
            ],

            [
                'slug'             => 'phenol-fecl3-test',
                'title'            => 'Identification of Phenols: Iron(III) Chloride Test',
                'topic'            => 'Organic Chemistry',
                'difficulty'       => 'medium',
                'grade_level'      => 10,
                'prerequisite_slug'=> 'carboxylic-acids-test',
                'chemical_equation'=> 'C₆H₅OH + FeCl₃ → [Fe(OC₆H₅)₆]³⁻ complex (intense violet/purple colour).',
                'explanatory_note' => 'Iron(III) chloride gives an intense purple or violet colour with phenols due to complex formation between Fe³⁺ and the phenolate oxygen. Carboxylic acids give a pale yellow colour; alcohols give no change. This distinguishes the phenolic OH group from other oxygen-containing functional groups.',
                'ar_available'     => false,
                'base_points'      => 70,
                'estimated_minutes'=> 20,
                'emoji'            => '💜',
                'card_gradient'    => 'linear-gradient(135deg,#F3E5F5,#9575CD)',
                'steps' => [
                    ['step_order' => 1, 'title' => 'Prepare the test solutions', 'instruction_text' => 'Prepare dilute solutions of phenol, ethanoic acid, ethanol and aspirin (crushed tablet dissolved in ethanol) in separate test tubes.', 'preauthored_guidance' => 'Use very dilute solutions (≤0.1 mol dm⁻³). The FeCl₃ test is sensitive — highly concentrated samples can give misleading results due to secondary reactions.', 'item_emoji' => '🧪', 'action_label' => 'Prepare solutions'],
                    ['step_order' => 2, 'title' => 'Add iron(III) chloride solution', 'instruction_text' => 'Add 2–3 drops of neutral FeCl₃ solution to each test tube.', 'preauthored_guidance' => 'The FeCl₃ solution should be close to neutral (pH ~3). Strongly acidic FeCl₃ can suppress the colour reaction and give false negatives. Prepare by adding a trace of NaOH until the initial brown precipitate just dissolves.', 'item_emoji' => '🟤', 'action_label' => 'Add FeCl₃'],
                    ['step_order' => 3, 'title' => 'Observe colours immediately', 'instruction_text' => 'Note an intense purple or violet colour as positive for phenol. Note pale yellow or no change for the others.', 'preauthored_guidance' => 'The purple colour appears immediately for phenols. The yellow from carboxylic acids fades quickly. Alcohols give no colour change. Record while the colours are fresh.', 'item_emoji' => '🔍', 'action_label' => 'Observe colours'],
                    ['step_order' => 4, 'title' => 'Interpret the aspirin result', 'instruction_text' => 'Note whether aspirin gives a positive test. Consider what functional group this implies.', 'preauthored_guidance' => 'Aspirin is a phenolic ester (salicylate). An intact phenol group gives a positive FeCl₃ result. If the aspirin tablet has been stored improperly, partial hydrolysis to salicylic acid gives a strongly positive result.', 'item_emoji' => '💊', 'action_label' => 'Interpret aspirin'],
                ],
                'quiz' => [
                    // correct_options: [2,0,3,1]
                    ['prompt' => 'The intense purple colour with FeCl₃ solution indicates the presence of:', 'options' => ['A carboxylic acid group', 'An aldehyde group', 'A phenolic OH group', 'An ester group'], 'correct_option' => 2, 'explanation' => 'The phenolate oxygen coordinates to Fe³⁺ to form a deeply coloured complex. This is specific to the phenolic OH — alcohols and carboxylic acids do not give this intense purple.'],
                    ['prompt' => 'Carboxylic acids give a pale yellow colour (not purple) with FeCl₃ because:', 'options' => ['They form a less stable complex with Fe³⁺ that gives yellow-brown rather than purple', 'They do not react with iron at all', 'They precipitate Fe(OH)₃ immediately', 'They reduce Fe³⁺ to Fe²⁺ which is yellow'], 'correct_option' => 0, 'explanation' => 'Carboxylate groups can coordinate to Fe³⁺ but the resulting complex has a different geometry and electronic structure, giving a yellow-brown colour rather than the intense purple of the phenol complex.'],
                    ['prompt' => 'The FeCl₃ solution used in this test should be close to neutral (pH ~3) because:', 'options' => ['Alkaline FeCl₃ is too coloured to use', 'Acid increases the stability of the phenol complex', 'Neutral conditions prevent interference from the Fe³⁺ ions themselves', 'Strongly acidic FeCl₃ suppresses the colour reaction and can give false negatives'], 'correct_option' => 3, 'explanation' => 'In strongly acidic conditions, phenol is not deprotonated to phenolate and the complex does not form efficiently. Near-neutral FeCl₃ allows the complex to develop properly.'],
                    ['prompt' => 'Aspirin giving a positive FeCl₃ test indicates it contains:', 'options' => ['A ketone group', 'A phenolic functional group (or its hydrolysis product salicylic acid)', 'An aldehyde group', 'A carboxylate salt'], 'correct_option' => 1, 'explanation' => 'Aspirin is acetylsalicylic acid — a phenolic ester. The phenol ring of the salicylate gives the FeCl₃ positive result, particularly if any hydrolysis to free salicylic acid has occurred.'],
                ],
            ],

            [
                'slug'             => 'kmno4-saturation-test',
                'title'            => 'KMnO₄ Test for Unsaturation',
                'topic'            => 'Organic Chemistry',
                'difficulty'       => 'medium',
                'grade_level'      => 10,
                'prerequisite_slug'=> 'carboxylic-acids-test',
                'chemical_equation'=> '3RCH=CHR\' + 2KMnO₄ + 4H₂O → 3RCHOH-CHOHR\' + 2MnO₂ + 2KOH (cold dilute, acidic conditions).',
                'explanatory_note' => 'Dilute cold acidified potassium permanganate rapidly decolourises in the presence of a carbon–carbon double bond. Saturated compounds do not react and the purple colour persists. This test distinguishes saturated from unsaturated organic compounds.',
                'ar_available'     => false,
                'base_points'      => 70,
                'estimated_minutes'=> 20,
                'emoji'            => '🟣',
                'card_gradient'    => 'linear-gradient(135deg,#F3E5F5,#9575CD)',
                'steps' => [
                    ['step_order' => 1, 'title' => 'Prepare dilute acidified KMnO₄', 'instruction_text' => 'Add a few drops of dilute H₂SO₄ to the KMnO₄ solution to acidify it.', 'preauthored_guidance' => 'Acidification is essential. Alkaline KMnO₄ is a much stronger oxidant and will oxidise many saturated compounds, giving false positives and making the test non-specific.', 'item_emoji' => '🧪', 'action_label' => 'Acidify KMnO₄'],
                    ['step_order' => 2, 'title' => 'Test cyclohexene (unsaturated)', 'instruction_text' => 'Add 5 drops of cyclohexene to 2 cm³ of the acidified KMnO₄. Shake and note the colour change.', 'preauthored_guidance' => 'Rapid decolourisation within seconds is a positive result. The purple MnO₄⁻ is reduced to colourless Mn²⁺ (or brown MnO₂ under less acidic conditions).', 'item_emoji' => '🔵', 'action_label' => 'Test unsaturated compound'],
                    ['step_order' => 3, 'title' => 'Test cyclohexane (saturated)', 'instruction_text' => 'Repeat with cyclohexane. The purple colour should persist for at least two minutes.', 'preauthored_guidance' => 'Slow fading over many minutes is due to oxidisable impurities, not C=C bonds. The key criterion is rapid decolourisation. Record the time for any colour change.', 'item_emoji' => '🟣', 'action_label' => 'Test saturated compound'],
                    ['step_order' => 4, 'title' => 'Test with natural samples', 'instruction_text' => 'Test ethanoic acid and a vegetable oil. Compare with the previous results.', 'preauthored_guidance' => 'Ethanoic acid (saturated) should show no rapid decolourisation. Vegetable oil contains unsaturated fatty acid chains and should decolourise rapidly, indicating C=C bonds.', 'item_emoji' => '🫒', 'action_label' => 'Test natural samples'],
                ],
                'quiz' => [
                    // correct_options: [3,1,2,0]
                    ['prompt' => 'Dilute acidified KMnO₄ decolourises rapidly in the presence of:', 'options' => ['Any organic compound', 'Saturated compounds only', 'Acids and alcohols specifically', 'A carbon–carbon double bond (alkene)'], 'correct_option' => 3, 'explanation' => 'The C=C double bond is readily oxidised by KMnO₄. Saturated C–C single bonds are much more resistant to oxidation under these mild conditions.'],
                    ['prompt' => 'The KMnO₄ must be acidified (not alkaline) because:', 'options' => ['Alkaline solutions are more intensely purple', 'Alkaline KMnO₄ is a stronger oxidant that gives false positives with saturated compounds', 'Acid makes the solution purple', 'Acid prevents the KMnO₄ from reacting with water'], 'correct_option' => 1, 'explanation' => 'In alkaline conditions, KMnO₄ is a powerful enough oxidant to attack many saturated bonds, making the test non-specific. In mildly acidic conditions it reacts selectively with C=C bonds.'],
                    ['prompt' => 'The product of cold, dilute acidified KMnO₄ oxidation of a simple alkene is:', 'options' => ['A carboxylic acid', 'A ketone', 'A diol (glycol)', 'An aldehyde'], 'correct_option' => 2, 'explanation' => 'Cold, dilute, acidified KMnO₄ adds two OH groups across the double bond to give a 1,2-diol (glycol). Hot concentrated KMnO₄ cleaves the double bond.'],
                    ['prompt' => 'Cyclohexane shows no decolourisation of KMnO₄ because it is:', 'options' => ['A saturated ring compound with no C=C double bond to be oxidised', 'Insoluble in aqueous KMnO₄', 'A gas that escapes before reacting', 'More reactive than cyclohexene and consumes the KMnO₄ differently'], 'correct_option' => 0, 'explanation' => 'Cyclohexane has only C–C single bonds and C–H bonds, which are not oxidised by cold dilute acidified KMnO₄. Without a C=C bond, the purple colour persists.'],
                ],
            ],

            [
                'slug'             => 'sugar-decomposition',
                'title'            => 'Dehydration of Sugar by Concentrated H₂SO₄',
                'topic'            => 'Organic Chemistry',
                'difficulty'       => 'easy',
                'grade_level'      => 10,
                'prerequisite_slug'=> null,
                'chemical_equation'=> 'C₁₂H₂₂O₁₁(s) → 12C(s) + 11H₂O(g) [dehydration by concentrated H₂SO₄]; the carbon expands as steam is produced.',
                'explanatory_note' => 'Concentrated sulfuric acid is a powerful dehydrating agent. It removes hydrogen and oxygen from sucrose in the ratio found in water, leaving behind pure carbon. The reaction is exothermic; the steam produced expands the carbon into a dramatic column. All work is done in a fume cupboard.',
                'ar_available'     => false,
                'base_points'      => 55,
                'estimated_minutes'=> 20,
                'emoji'            => '🍬',
                'card_gradient'    => 'linear-gradient(135deg,#FFF9C4,#FFD54F)',
                'steps' => [
                    ['step_order' => 1, 'title' => 'Set up in the fume cupboard', 'instruction_text' => 'Weigh about 20 g of sucrose into a 250 cm³ beaker inside the fume cupboard. Lower the sash.', 'preauthored_guidance' => 'This experiment must be in the fume cupboard. Concentrated H₂SO₄ fumes and the steam produced carry acid. All observers stand clear.', 'item_emoji' => '🏭', 'action_label' => 'Set up in fume cupboard'],
                    ['step_order' => 2, 'title' => 'Add concentrated H₂SO₄', 'instruction_text' => 'Pour 10 cm³ of concentrated H₂SO₄ onto the sugar and step back.', 'preauthored_guidance' => 'The reaction takes a few seconds to begin then becomes vigorous. Do not lean over the beaker. The exothermic reaction generates significant heat and steam.', 'item_emoji' => '⚗️', 'action_label' => 'Add acid'],
                    ['step_order' => 3, 'title' => 'Observe the carbon column', 'instruction_text' => 'Watch the black carbon column form and rise from the beaker.', 'preauthored_guidance' => 'Steam produced inside the carbon matrix forces it upward. This is the same expanding-gas principle as many chemical demonstrations but driven by dehydration rather than gas evolution.', 'item_emoji' => '🏔️', 'action_label' => 'Observe column'],
                    ['step_order' => 4, 'title' => 'Allow to cool and dispose safely', 'instruction_text' => 'Wait until the reaction stops and the beaker is cool. Dispose of the porous carbon and residual acid in the designated waste container.', 'preauthored_guidance' => 'Do not add water to cool the product. The beaker contains concentrated acid; diluting it rapidly generates more heat. Cool naturally, then neutralise the waste with sodium carbonate solution before disposal.', 'item_emoji' => '♻️', 'action_label' => 'Cool and dispose'],
                ],
                'quiz' => [
                    // correct_options: [0,3,1,2]
                    ['prompt' => 'The black solid produced in this experiment is:', 'options' => ['Carbon — the hydrogen and oxygen have been removed as water by the dehydrating agent', 'Carbon dioxide formed in a combustion reaction', 'Charcoal from burning', 'Sulfur released from the acid'], 'correct_option' => 0, 'explanation' => 'H₂SO₄ removes H and O in the 2:1 ratio found in water, leaving behind elemental carbon. No combustion occurs — the carbon is produced by dehydration, not burning.'],
                    ['prompt' => 'The carbon column rises from the beaker because:', 'options' => ['Carbon dioxide gas is produced and carries the carbon upward', 'Sucrose is less dense than air', 'The reaction is endothermic and contracts the mixture', 'Steam produced inside the carbon matrix forces it upward as it expands'], 'correct_option' => 3, 'explanation' => 'Water vapour (steam) is released within the forming carbon solid. As the steam expands, it pushes the carbon upward, creating the expanding column effect.'],
                    ['prompt' => 'Concentrated H₂SO₄ is described as a dehydrating agent because it:', 'options' => ['Adds water to organic molecules', 'Removes hydrogen and oxygen in the ratio found in water from organic molecules', 'Is produced when SO₃ is added to water', 'Reacts with water to form hydrated sulfate'], 'correct_option' => 1, 'explanation' => 'A dehydrating agent removes H and O in the 2:1 ratio that corresponds to water. This is different from drying (removing free water): dehydration chemically removes atoms that are part of the organic molecule.'],
                    ['prompt' => 'Water must NOT be added to cool the product because:', 'options' => ['It would re-dissolve the carbon', 'Carbon reacts with water to form CO₂', 'Diluting concentrated H₂SO₄ is highly exothermic and could cause violent spattering of hot acid', 'Water would neutralise the acid before it could be disposed of safely'], 'correct_option' => 2, 'explanation' => 'Diluting concentrated H₂SO₄ releases a large amount of heat. Adding water to a hot beaker of concentrated acid could cause explosive boiling and spray hot acid.'],
                ],
            ],

            [
                'slug'             => 'water-softening',
                'title'            => 'Methods of Water Softening',
                'topic'            => 'Water Chemistry',
                'difficulty'       => 'easy',
                'grade_level'      => 10,
                'prerequisite_slug'=> null,
                'chemical_equation'=> 'Temporary hardness: Ca(HCO₃)₂ → CaCO₃(s) + H₂O + CO₂ [boiling]. Permanent hardness: Ca²⁺ + CO₃²⁻ → CaCO₃(s) [Na₂CO₃ treatment]. Ion exchange: Ca²⁺ + 2Na⁺(resin) ⇌ Ca²⁺(resin) + 2Na⁺.',
                'explanatory_note' => 'Hard water contains dissolved Ca²⁺ and Mg²⁺ ions that prevent soap from lathering and form scale. Temporary hardness (from bicarbonates) is removed by boiling. Permanent hardness (from sulfates and chlorides) requires Na₂CO₃ or ion-exchange resin.',
                'ar_available'     => false,
                'base_points'      => 55,
                'estimated_minutes'=> 25,
                'emoji'            => '💧',
                'card_gradient'    => 'linear-gradient(135deg,#E8F4FD,#4FC3F7)',
                'steps' => [
                    ['step_order' => 1, 'title' => 'Test hardness with soap solution', 'instruction_text' => 'Add soap solution drop by drop to samples of hard and soft water. Count the drops needed for a permanent lather.', 'preauthored_guidance' => 'More drops = harder water. The lather must be permanent (lasting at least 30 seconds). A temporary froth does not count as true lather.', 'item_emoji' => '🧼', 'action_label' => 'Test with soap'],
                    ['step_order' => 2, 'title' => 'Remove temporary hardness by boiling', 'instruction_text' => 'Boil a portion of the hard water for 5 minutes. Filter, cool and re-test with soap solution.', 'preauthored_guidance' => 'Boiling only removes temporary hardness (bicarbonates). If the boiled sample still needs many soap drops, permanent hardness is also present — it cannot be removed by boiling alone.', 'item_emoji' => '🔥', 'action_label' => 'Boil sample'],
                    ['step_order' => 3, 'title' => 'Remove hardness with Na₂CO₃', 'instruction_text' => 'Add Na₂CO₃ to a portion of the original hard water. Filter the precipitate of CaCO₃ and test the filtrate with soap.', 'preauthored_guidance' => 'Na₂CO₃ removes both temporary and permanent hardness by precipitating Ca²⁺ as CaCO₃. Filter before testing — residual CaCO₃ particles react with soap and interfere with the lather test.', 'item_emoji' => '🧪', 'action_label' => 'Treat with Na₂CO₃'],
                    ['step_order' => 4, 'title' => 'Soften with ion-exchange resin', 'instruction_text' => 'Pass the hard water through an ion-exchange column. Test the eluate with soap solution.', 'preauthored_guidance' => 'The resin exchanges Ca²⁺ and Mg²⁺ for Na⁺ or H⁺. The eluate should require very few drops of soap to lather, confirming near-complete softening.', 'item_emoji' => '🔄', 'action_label' => 'Ion-exchange column'],
                ],
                'quiz' => [
                    // correct_options: [1,0,2,3]
                    ['prompt' => 'Hard water is caused by dissolved:', 'options' => ['Chloride ions only', 'Calcium and magnesium ions', 'Sodium chloride (table salt)', 'Potassium ions from fertiliser run-off'], 'correct_option' => 1, 'explanation' => 'Ca²⁺ and Mg²⁺ ions react with soap to form an insoluble scum (calcium/magnesium stearate), preventing lather formation. Na⁺ and K⁺ soaps are soluble and lather normally.'],
                    ['prompt' => 'Boiling removes only temporary hardness because:', 'options' => ['The bicarbonates decompose on heating to form insoluble CaCO₃, which can be filtered off; permanent hardness salts (sulfates, chlorides) do not decompose on boiling', 'Boiling removes all dissolved solids from water', 'Calcium does not dissolve in hot water', 'Sodium carbonate is needed to remove temporary hardness'], 'correct_option' => 0, 'explanation' => 'Ca(HCO₃)₂ decomposes on heating to CaCO₃ (insoluble, filtered off), water and CO₂. CaSO₄ and CaCl₂ are thermally stable and remain in solution after boiling.'],
                    ['prompt' => 'Ion-exchange resin softens water by:', 'options' => ['Filtering out Ca²⁺ by size exclusion', 'Decomposing Ca²⁺ ions into calcium metal', 'Replacing Ca²⁺ and Mg²⁺ ions with Na⁺ or H⁺ ions that do not cause hardness', 'Precipitating CaCO₃ within the resin beads'], 'correct_option' => 2, 'explanation' => 'The resin contains exchangeable Na⁺ or H⁺ ions. As hard water passes through, Ca²⁺ and Mg²⁺ bind to the resin and Na⁺ or H⁺ are released. The water leaving contains only soft-water cations.'],
                    ['prompt' => 'The soap lather test indicates water hardness because:', 'options' => ['Hard water is more coloured and absorbs soap', 'Soft water is more acidic and dissolves soap faster', 'Hard water is denser so more soap is needed to create bubbles', 'Ca²⁺ and Mg²⁺ ions react with soap to form insoluble scum, consuming it before lather can form'], 'correct_option' => 3, 'explanation' => 'Soap (sodium stearate) reacts with Ca²⁺ and Mg²⁺ to give calcium/magnesium stearate — the grey scum. Each drop of soap is wasted on forming scum until all the Ca²⁺ and Mg²⁺ have been precipitated.'],
                ],
            ],

            [
                'slug'             => 'electrolysis-water',
                'title'            => 'Electrolysis of Water',
                'topic'            => 'Electrochemistry',
                'difficulty'       => 'medium',
                'grade_level'      => 10,
                'prerequisite_slug'=> null,
                'chemical_equation'=> '2H₂O(l) → 2H₂(g) + O₂(g); cathode: 4H⁺ + 4e⁻ → 2H₂; anode: 2H₂O → O₂ + 4H⁺ + 4e⁻.',
                'explanatory_note' => 'Pure water is a very poor conductor, so dilute H₂SO₄ is added to provide ions. Hydrogen collects at the cathode in twice the volume of oxygen at the anode, confirming the 2:1 molar ratio in water. Both gases are identified: H₂ gives a squeaky pop with a lit splint; O₂ relights a glowing splint.',
                'ar_available'     => true,
                'base_points'      => 75,
                'estimated_minutes'=> 25,
                'emoji'            => '⚡',
                'card_gradient'    => 'linear-gradient(135deg,#E8F4FD,#29B6F6)',
                'steps' => [
                    ['step_order' => 1, 'title' => 'Set up the Hofmann voltameter', 'instruction_text' => 'Fill the apparatus with dilute H₂SO₄ (0.5 mol dm⁻³). Ensure there are no air bubbles in the collection tubes.', 'preauthored_guidance' => 'Air bubbles in the tubes give a false reading for the gas volumes. Invert the filled apparatus once and let the liquid settle before connecting the power supply.', 'item_emoji' => '⚗️', 'action_label' => 'Fill voltameter'],
                    ['step_order' => 2, 'title' => 'Apply direct current', 'instruction_text' => 'Connect to a d.c. supply at 6–12 V. Bubbles should form at both electrodes immediately.', 'preauthored_guidance' => 'The cathode produces more bubbles (hydrogen is less soluble and comes off faster). The anode produces fewer, larger bubbles (oxygen is more soluble). If no bubbles appear, check all connections.', 'item_emoji' => '🔌', 'action_label' => 'Apply current'],
                    ['step_order' => 3, 'title' => 'Measure the volumes', 'instruction_text' => 'Allow gas to collect until each tube is about three-quarters full. Record the volumes of both gases.', 'preauthored_guidance' => 'The cathode tube (H₂) should be approximately twice the volume of the anode tube (O₂). A significant difference from 2:1 may indicate dissolved oxygen or leaks. Record the exact values.', 'item_emoji' => '📏', 'action_label' => 'Measure volumes'],
                    ['step_order' => 4, 'title' => 'Test the gases', 'instruction_text' => 'Hold a lit splint at the cathode tube opening — listen for a squeaky pop. Hold a glowing splint at the anode tube — it should relight.', 'preauthored_guidance' => 'H₂ gives a squeaky pop with a lit splint; O₂ relights a glowing splint. If neither test works, the electrodes may be connected with reversed polarity. Swap the leads and repeat.', 'item_emoji' => '🔥', 'action_label' => 'Identify gases'],
                ],
                'quiz' => [
                    // correct_options: [2,1,3,0]
                    ['prompt' => 'The gas collected at the cathode (negative electrode) is:', 'options' => ['Oxygen', 'Carbon dioxide', 'Hydrogen', 'Chlorine'], 'correct_option' => 2, 'explanation' => 'At the cathode, reduction occurs: 4H⁺ + 4e⁻ → 2H₂. H⁺ ions gain electrons and are reduced to hydrogen gas.'],
                    ['prompt' => 'The volume ratio of hydrogen to oxygen produced is approximately:', 'options' => ['1:1', '2:1 (H₂ : O₂)', '1:2 (H₂ : O₂)', '4:1 (H₂ : O₂)'], 'correct_option' => 1, 'explanation' => 'From the equation 2H₂O → 2H₂ + O₂, two moles of H₂ are produced for every one mole of O₂, giving a 2:1 volume ratio.'],
                    ['prompt' => 'Dilute H₂SO₄ is added to the water because:', 'options' => ['H₂SO₄ decomposes to give H₂ and O₂ directly', 'It makes the water denser', 'It raises the boiling point of the water', 'Pure water conducts very poorly; the acid provides ions to carry the current'], 'correct_option' => 3, 'explanation' => 'Pure water has very low [H⁺] and [OH⁻], making it a very poor conductor. The dilute H₂SO₄ provides a high concentration of ions to carry the current through the solution.'],
                    ['prompt' => 'The test for hydrogen gas at the cathode is:', 'options' => ['A burning splint produces a squeaky pop', 'A glowing splint relights', 'Limewater turns milky', 'The gas is colourless and has a distinctive smell'], 'correct_option' => 0, 'explanation' => 'Hydrogen ignites in air with a characteristic high-pitched squeaky pop or "bark". This is the standard test to confirm hydrogen gas.'],
                ],
            ],

            [
                'slug'             => 'copper-plating',
                'title'            => 'Copper Plating by Electrolysis',
                'topic'            => 'Electrochemistry',
                'difficulty'       => 'medium',
                'grade_level'      => 10,
                'prerequisite_slug'=> 'electrolysis-water',
                'chemical_equation'=> 'Cathode: Cu²⁺(aq) + 2e⁻ → Cu(s) [reduction]; Anode: Cu(s) → Cu²⁺(aq) + 2e⁻ [oxidation].',
                'explanatory_note' => 'Electroplating deposits a thin adherent layer of copper on a metal surface using electrolysis. With a copper anode and copper sulphate electrolyte, copper dissolves from the anode and deposits at the cathode, keeping the electrolyte concentration constant. Surface preparation is critical for adhesion.',
                'ar_available'     => true,
                'base_points'      => 75,
                'estimated_minutes'=> 30,
                'emoji'            => '🟤',
                'card_gradient'    => 'linear-gradient(135deg,#FFD6C8,#FF8A50)',
                'steps' => [
                    ['step_order' => 1, 'title' => 'Prepare the cathode surface', 'instruction_text' => 'Polish the iron electrode with emery paper. Rinse with acetone, then with distilled water. Weigh and record the mass.', 'preauthored_guidance' => 'A clean, grease-free surface is essential for adhesion. Oil or oxide causes the copper deposit to be powdery and non-adherent. Acetone removes grease that water alone cannot dissolve.', 'item_emoji' => '✨', 'action_label' => 'Clean cathode'],
                    ['step_order' => 2, 'title' => 'Set up the electrolytic cell', 'instruction_text' => 'Place a copper anode and the prepared iron cathode in copper sulphate solution. Connect to a d.c. supply at 3–6 V, cathode to the negative terminal.', 'preauthored_guidance' => 'Connecting the object to the positive terminal makes it the anode — it will dissolve rather than being plated. The cathode is always the object to be coated (negative terminal).', 'item_emoji' => '🔌', 'action_label' => 'Wire up cell'],
                    ['step_order' => 3, 'title' => 'Electroplate for 10–15 minutes', 'instruction_text' => 'Apply current and observe the colour change on the cathode surface.', 'preauthored_guidance' => 'A shiny pink copper deposit is a good result. A dull brown deposit means too high a current density. A powdery deposit means the surface was not clean. Reduce the current if the deposit looks rough.', 'item_emoji' => '⚡', 'action_label' => 'Plate copper'],
                    ['step_order' => 4, 'title' => 'Weigh and calculate', 'instruction_text' => 'Remove the cathode, rinse in distilled water, dry gently and weigh. Note the mass of copper deposited. Weigh the anode too.', 'preauthored_guidance' => 'Dry both electrodes fully before weighing — surface water adds mass. The mass gain at the cathode should approximately equal the mass loss at the anode, confirming conservation of mass.', 'item_emoji' => '⚖️', 'action_label' => 'Compare masses'],
                ],
                'quiz' => [
                    // correct_options: [3,0,1,2]
                    ['prompt' => 'In copper plating, copper deposits at the cathode because:', 'options' => ['The anode repels copper ions toward the cathode', 'The cathode is made of iron which attracts copper', 'The electrolyte decomposes to release copper', 'Cu²⁺ ions are reduced at the negative electrode by gaining electrons'], 'correct_option' => 3, 'explanation' => 'Reduction occurs at the cathode (negative electrode): Cu²⁺ + 2e⁻ → Cu. Cu²⁺ ions from the solution gain electrons and are deposited as copper metal.'],
                    ['prompt' => 'The copper anode gradually loses mass because:', 'options' => ['It is oxidised: Cu → Cu²⁺ + 2e⁻, replenishing copper ions in the electrolyte', 'It is washed away by the flowing electrolyte', 'Gravity causes copper to fall to the bottom of the cell', 'The anode decomposes water instead of dissolving'], 'correct_option' => 0, 'explanation' => 'At the anode (positive electrode), oxidation occurs: Cu → Cu²⁺ + 2e⁻. Copper metal dissolves, maintaining the Cu²⁺ concentration in the electrolyte constant.'],
                    ['prompt' => 'A powdery, non-adherent copper deposit indicates:', 'options' => ['The current was too low', 'The cathode surface was not properly cleaned before plating', 'The electrolyte was too dilute', 'The anode was too large'], 'correct_option' => 1, 'explanation' => 'Oil or oxide on the cathode surface prevents copper atoms from bonding to the substrate. They form a mechanically weak deposit that peels off easily.'],
                    ['prompt' => 'The mass gained at the cathode should equal the mass lost at the anode because:', 'options' => ['Both electrodes are the same metal', 'The electrolyte has a fixed, unchanging composition', 'Conservation of mass: copper dissolving at the anode and depositing at the cathode are the same process in reverse', 'The current is equal at both electrodes by Kirchhoff\'s law'], 'correct_option' => 2, 'explanation' => 'Every Cu²⁺ ion that deposits at the cathode came from one Cu atom that dissolved at the anode. The total mass of copper is conserved — it simply moves from anode to cathode via the electrolyte.'],
                ],
            ],

            [
                'slug'             => 'displacement-reactions',
                'title'            => 'Displacement Reactions and the Reactivity Series',
                'topic'            => 'Reactivity Series',
                'difficulty'       => 'medium',
                'grade_level'      => 10,
                'prerequisite_slug'=> null,
                'chemical_equation'=> 'Fe(s) + CuSO₄(aq) → FeSO₄(aq) + Cu(s); Zn(s) + CuSO₄(aq) → ZnSO₄(aq) + Cu(s); Cu + ZnSO₄(aq) → no reaction.',
                'explanatory_note' => 'A more reactive metal displaces a less reactive one from its salt solution. The reactivity series predicts the direction: a metal higher in the series always displaces one below it. This experiment provides direct visual evidence for the order of reactivity among zinc, iron and copper.',
                'ar_available'     => true,
                'base_points'      => 75,
                'estimated_minutes'=> 20,
                'emoji'            => '⬇️',
                'card_gradient'    => 'linear-gradient(135deg,#C8F7E4,#2E7D57)',
                'steps' => [
                    ['step_order' => 1, 'title' => 'Set up the test tubes', 'instruction_text' => 'Pour 5 cm³ of copper sulphate solution into two test tubes (tubes 1 and 2) and 5 cm³ of zinc sulphate into tube 3.', 'preauthored_guidance' => 'Use fresh solutions and the same volume in each tube so the colour changes can be compared fairly. Iron(II) sulphate oxidises in air to Fe³⁺; use fresh solution if testing iron salt reactions.', 'item_emoji' => '🧪', 'action_label' => 'Prepare test tubes'],
                    ['step_order' => 2, 'title' => 'Add the metal strips', 'instruction_text' => 'Sand each metal strip lightly to remove surface oxide. Add: an iron strip to tube 1, a zinc strip to tube 2, a copper strip to tube 3.', 'preauthored_guidance' => 'Sanding removes the oxide layer. An oxide-covered surface prevents direct metal–ion contact at the interface, giving a false-negative result for a reaction that should occur.', 'item_emoji' => '⚙️', 'action_label' => 'Add metal strips'],
                    ['step_order' => 3, 'title' => 'Observe for 10 minutes', 'instruction_text' => 'Record changes in solution colour and metal surface at 2-minute intervals.', 'preauthored_guidance' => 'Tubes 1 and 2 (iron and zinc in CuSO₄) should show the blue colour fading and a pink-brown copper deposit forming. Tube 3 (copper in ZnSO₄) should show no change, confirming copper is less reactive than zinc.', 'item_emoji' => '🔍', 'action_label' => 'Observe reactions'],
                    ['step_order' => 4, 'title' => 'Rank the metals', 'instruction_text' => 'From your observations, arrange zinc, iron and copper in order of decreasing reactivity.', 'preauthored_guidance' => 'Evidence of displacement (metal A deposits on metal B) proves A is more reactive than B. Absence of displacement proves the added metal is less reactive. Use only your observations — do not simply quote the textbook series.', 'item_emoji' => '📊', 'action_label' => 'Rank by reactivity'],
                ],
                'quiz' => [
                    // correct_options: [0,2,3,1]
                    ['prompt' => 'In a displacement reaction, a more reactive metal displaces a less reactive one because:', 'options' => ['The more reactive metal has a greater tendency to lose electrons and form ions', 'The more reactive metal is always denser', 'The less reactive metal dissolves first', 'The temperature of the solution rises, driving the reaction'], 'correct_option' => 0, 'explanation' => 'A more reactive metal is a stronger reducing agent — it more readily loses electrons. It can therefore reduce the ions of a less reactive metal, depositing the less reactive metal.'],
                    ['prompt' => 'When an iron strip is placed in copper sulphate solution:', 'options' => ['The iron dissolves and nothing deposits on its surface', 'No change is observed', 'A pink-brown copper deposit forms on the iron and the blue colour of the solution fades', 'Hydrogen gas is produced'], 'correct_option' => 2, 'explanation' => 'Fe + Cu²⁺ → Fe²⁺ + Cu. Iron dissolves as Fe²⁺ (solution colour changes from blue toward pale green/colourless) and copper deposits on the iron surface.'],
                    ['prompt' => 'Copper does NOT displace zinc from zinc sulphate solution because:', 'options' => ['Copper is denser than zinc', 'Zinc sulphate is insoluble in water', 'Copper and zinc have exactly equal reactivity', 'Copper is lower in the reactivity series than zinc and cannot reduce Zn²⁺ ions'], 'correct_option' => 3, 'explanation' => 'For displacement to occur, the metal added must be above the metal in solution in the reactivity series. Copper is below zinc, so it cannot reduce Zn²⁺ and no reaction occurs.'],
                    ['prompt' => 'The ionic equation for iron displacing copper from copper sulphate is:', 'options' => ['Fe²⁺(aq) + Cu(s) → Fe(s) + Cu²⁺(aq)', 'Fe(s) + Cu²⁺(aq) → Fe²⁺(aq) + Cu(s)', 'Fe(s) + Cu(s) → FeCu(s)', 'Fe²⁺(aq) + Cu²⁺(aq) → Fe(s) + Cu(s)'], 'correct_option' => 1, 'explanation' => 'Iron is oxidised (Fe → Fe²⁺ + 2e⁻); copper ions are reduced (Cu²⁺ + 2e⁻ → Cu). Net ionic equation: Fe + Cu²⁺ → Fe²⁺ + Cu.'],
                ],
            ],

            [
                'slug'             => 'confirmatory-ion-tests',
                'title'            => 'Confirmatory Tests for Common Ions',
                'topic'            => 'Analytical Chemistry',
                'difficulty'       => 'hard',
                'grade_level'      => 10,
                'prerequisite_slug'=> 'flame-test',
                'chemical_equation'=> 'Ba²⁺ + SO₄²⁻ → BaSO₄(s) white; Ag⁺ + Cl⁻ → AgCl(s) white; Fe³⁺ + 3OH⁻ → Fe(OH)₃(s) rust-brown; Cu²⁺ + 2OH⁻ → Cu(OH)₂(s) blue.',
                'explanatory_note' => 'Confirmatory ion tests use specific reagents that give characteristic precipitates or colours with target ions. Tests are hierarchical: flame test first for alkali metal cations, then wet chemical tests for remaining cations and anions. Acidification before adding BaCl₂ or AgNO₃ prevents carbonate and sulfite ions from giving false-positive white precipitates.',
                'ar_available'     => false,
                'base_points'      => 85,
                'estimated_minutes'=> 30,
                'emoji'            => '🔬',
                'card_gradient'    => 'linear-gradient(135deg,#C8F7E4,#4CAF82)',
                'steps' => [
                    ['step_order' => 1, 'title' => 'Test for sulfate ion (SO₄²⁻)', 'instruction_text' => 'Acidify the sample with dilute HCl, then add BaCl₂ solution. A white precipitate of BaSO₄ that is insoluble in excess HCl confirms SO₄²⁻.', 'preauthored_guidance' => 'Acidification removes carbonate and sulfite ions, which also form white barium precipitates. Only BaSO₄ is insoluble in dilute HCl — this distinguishes sulfate from the interferences.', 'item_emoji' => '⬜', 'action_label' => 'Test for sulfate'],
                    ['step_order' => 2, 'title' => 'Test for halide ions (Cl⁻, Br⁻, I⁻)', 'instruction_text' => 'Acidify with dilute HNO₃, then add AgNO₃. White ppt = Cl⁻ (dissolves in dilute NH₃); cream ppt = Br⁻ (partially soluble); yellow ppt = I⁻ (insoluble in concentrated NH₃).', 'preauthored_guidance' => 'Acidify with HNO₃, never HCl (which would introduce Cl⁻ and give a false positive). The ammonia solubility test distinguishes Cl⁻ from Br⁻ and I⁻ — it is essential, not optional.', 'item_emoji' => '🧪', 'action_label' => 'Test for halides'],
                    ['step_order' => 3, 'title' => 'Test for iron ions', 'instruction_text' => 'Add dilute NaOH. Fe²⁺ gives a green-grey precipitate of Fe(OH)₂; Fe³⁺ gives a rust-brown precipitate of Fe(OH)₃.', 'preauthored_guidance' => 'Fe(OH)₂ oxidises in air to the orange-brown Fe(OH)₃, so observe immediately. A precipitate that starts green and turns brown suggests Fe(OH)₂ oxidising — record the initial colour.', 'item_emoji' => '🟤', 'action_label' => 'Test for iron ions'],
                    ['step_order' => 4, 'title' => 'Test for copper ions (Cu²⁺)', 'instruction_text' => 'Add dilute NaOH to give a light blue precipitate of Cu(OH)₂. Confirm: Al³⁺ also gives a white precipitate but dissolves in excess NaOH; Cu(OH)₂ does not.', 'preauthored_guidance' => 'Al(OH)₃ is amphoteric — it dissolves in both acids and excess alkali. Cu(OH)₂ does not dissolve in excess NaOH. This distinction separates Cu²⁺ from Al³⁺ in a mixed sample.', 'item_emoji' => '🔵', 'action_label' => 'Test for copper ions'],
                ],
                'quiz' => [
                    // correct_options: [1,3,2,0]
                    ['prompt' => 'The purpose of acidifying with HCl before adding BaCl₂ for the sulfate test is to:', 'options' => ['Dissolve the sample before the test', 'Remove carbonate and sulfite ions that also form white barium precipitates, so only sulfate is detected', 'Increase the solubility of BaSO₄', 'Change the colour of the BaSO₄ precipitate'], 'correct_option' => 1, 'explanation' => 'BaCO₃ and BaSO₃ are also white precipitates. Both are soluble in HCl. By acidifying first, these interfering ions are converted to CO₂/SO₂ gas and only BaSO₄ (insoluble in HCl) forms when BaCl₂ is added.'],
                    ['prompt' => 'The white AgCl precipitate is distinguished from AgBr by:', 'options' => ['Its higher density', 'Its colour in daylight — AgCl is pure white, AgBr is cream', 'Its reaction with dilute acid', 'Its solubility in dilute ammonia solution — AgCl dissolves in dilute NH₃; AgBr does not'], 'correct_option' => 3, 'explanation' => 'AgCl + 2NH₃ → [Ag(NH₃)₂]⁺ + Cl⁻. AgCl dissolves readily in dilute ammonia. AgBr requires concentrated ammonia. AgI is insoluble in ammonia at any concentration.'],
                    ['prompt' => 'Adding NaOH to a solution gives a rust-brown precipitate. This confirms:', 'options' => ['Cu²⁺ ions', 'Fe²⁺ ions', 'Fe³⁺ ions', 'Al³⁺ ions'], 'correct_option' => 2, 'explanation' => 'Fe³⁺ + 3OH⁻ → Fe(OH)₃. Iron(III) hydroxide is the characteristic rust-brown colour. Fe²⁺ gives a green-grey precipitate; Cu²⁺ gives a light blue precipitate.'],
                    ['prompt' => 'Cu(OH)₂ does NOT dissolve in excess NaOH (unlike Al(OH)₃) because:', 'options' => ['Cu(OH)₂ is not amphoteric — it cannot act as an acid toward OH⁻ ions the way Al(OH)₃ can', 'Copper is less reactive than aluminium', 'NaOH is not a strong enough base to dissolve Cu(OH)₂', 'Cu(OH)₂ decomposes to CuO before it can dissolve'], 'correct_option' => 0, 'explanation' => 'Al(OH)₃ is amphoteric: it acts as an acid toward OH⁻ to form aluminate [Al(OH)₄]⁻. Cu(OH)₂ does not have this property — it cannot act as an acid and therefore does not dissolve in excess alkali.'],
                ],
            ],

        ];

        // ── First pass: create experiments, steps and quiz questions ────────
        foreach ($experiments as $data) {
            $experiment = Experiment::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'title'             => $data['title'],
                    'topic'             => $data['topic'],
                    'difficulty'        => $data['difficulty'],
                    'grade_level'       => $data['grade_level'],
                    'chemical_equation' => $data['chemical_equation'],
                    'explanatory_note'  => $data['explanatory_note'],
                    'ar_available'      => $data['ar_available'],
                    'base_points'       => $data['base_points'],
                    'estimated_minutes' => $data['estimated_minutes'],
                    'emoji'             => $data['emoji'],
                    'card_gradient'     => $data['card_gradient'],
                    'is_published'      => true,
                ]
            );

            foreach ($data['steps'] as $step) {
                $experiment->steps()->updateOrCreate(
                    ['step_order' => $step['step_order']],
                    $step
                );
            }

            foreach ($data['quiz'] as $question) {
                $experiment->quizQuestions()->updateOrCreate(
                    ['prompt' => $question['prompt']],
                    $question
                );
            }
        }

        // ── Second pass: link prerequisites by slug (FR-03.3) ───────────────
        foreach ($experiments as $data) {
            if ($data['prerequisite_slug']) {
                Experiment::where('slug', $data['slug'])->update([
                    'prerequisite_experiment_id' => Experiment::where('slug', $data['prerequisite_slug'])->value('id'),
                ]);
            }
        }

        $this->keepOnlyTextbookExperiments();
    }

    /**
     * Decided 8 Nov 2026: only experiments that appear in the Punjab and Federal
     * textbooks are offered. Each published one gets the class and board of its book
     * (docs/experiment-catalogue.md). The rest stay in the database unpublished, so
     * nothing is lost if a practical notebook later confirms them.
     */
    public const TEXTBOOK = [
        'melting-points' => [9, 'federal'],            // Federal 9 Activity 12.2
        'boiling-points' => [9, 'federal'],            // Federal 9 Activity 12.3
        'solubility-temperature' => [9, 'both'],       // Punjab 9 p16, Federal 9 Activity 2.1
        'copper-sulphate-crystals' => [10, 'federal'], // Federal 10 Activity 6.3
        'dilution-hcl' => [10, 'federal'],             // Federal 10 Activity 3.1
        'standardise-hcl' => [10, 'federal'],          // Federal 10 Activity 3.2
        'acid-base-neutral' => [10, 'federal'],        // Federal 10 Activity 6.1
        'displacement-reactions' => [10, 'federal'],   // Federal 10 Activity 7.1
        'standardise-naoh' => [11, 'punjab'],          // Punjab 11 Chapter 16
        'confirmatory-ion-tests' => [11, 'punjab'],    // Punjab 11 Chapter 16
    ];

    private function keepOnlyTextbookExperiments(): void
    {
        Experiment::whereNotIn('slug', array_keys(self::TEXTBOOK))->update(['is_published' => false]);

        foreach (self::TEXTBOOK as $slug => [$grade, $board]) {
            Experiment::where('slug', $slug)->update([
                'grade_level' => $grade,
                'board' => $board,
                'is_published' => true,
            ]);
        }

        // A prerequisite that is no longer offered, or sits in another class pair or board,
        // would lock its experiment for good (FR-03.3), so such links are dropped.
        Experiment::where('is_published', true)->whereNotNull('prerequisite_experiment_id')->get()
            ->each(function (Experiment $e) {
                $pre = Experiment::find($e->prerequisite_experiment_id);
                $reachable = $pre && $pre->is_published
                    && in_array($pre->board, [$e->board, 'both'], true)
                    && abs($pre->grade_level - $e->grade_level) <= 1;
                if (! $reachable) {
                    $e->update(['prerequisite_experiment_id' => null]);
                }
            });
    }
}
