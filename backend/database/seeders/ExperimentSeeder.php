<?php

namespace Database\Seeders;

use App\Models\Experiment;
use Illuminate\Database\Seeder;

/**
 * The experiment library.
 *
 * FR-03.1 asks for no fewer than 30 experiments. Six are written out here in
 * full, matching the six in the prototype, each with its steps, its governing
 * equation (FR-03.6), its pre-authored guidance (NFR-06) and its quiz (FR-04.2).
 * The remaining twenty-four are content work, not code: add them to this array
 * or import them, and nothing else has to change.
 *
 * Every step carries preauthored_guidance. That column is NOT NULL for a
 * reason: a step without it would break NFR-06 the first time the AI service
 * timed out.
 */
class ExperimentSeeder extends Seeder
{
    public function run(): void
    {
        $experiments = [
            [
                'slug' => "volcano",
                'title' => "Volcano Eruption",
                'topic' => "Reactions",
                'difficulty' => "easy",
                'grade_level' => 9,
                'prerequisite_slug' => null,
                'chemical_equation' => "NaHCO₃(aq) + CH₃COOH(aq) → CH₃COONa(aq) + H₂O(l) + CO₂(g)",
                'explanatory_note' => "Sodium hydrogen carbonate is a base and ethanoic acid, the acid in vinegar, is a weak acid. Neutralisation produces a salt, water and carbon dioxide. The eruption is the carbon dioxide escaping as a foam, not an explosion: no heat is needed and nothing burns.",
                'ar_available' => true,
                'base_points' => 50,
                'estimated_minutes' => 20,
                'emoji' => "🌋",
                'card_gradient' => "linear-gradient(135deg,#FFF3E0,#FFCC80)",
                'steps' => [
                    ['step_order' => 1, 'title' => "Build your volcano model", 'instruction_text' => "Shape a paper cone around an empty cup. The cup is the crater and the cone only holds the shape.", 'preauthored_guidance' => "Tape the cone so it stands on its own before anything goes in the cup. A cone that collapses mid-reaction spills the contents across the bench.", 'item_emoji' => "🏔️", 'action_label' => "Place cone"],
                    ['step_order' => 2, 'title' => "Add the baking soda", 'instruction_text' => "Place two spoons of sodium hydrogen carbonate into the cup. This is the base.", 'preauthored_guidance' => "Two level spoons is enough. More powder does not make a bigger eruption, it only leaves unreacted solid at the bottom.", 'item_emoji' => "🥄", 'action_label' => "Add baking soda"],
                    ['step_order' => 3, 'title' => "Add a drop of detergent", 'instruction_text' => "One drop of washing-up liquid traps the gas as foam so the eruption is visible.", 'preauthored_guidance' => "One drop only. The detergent takes no part in the reaction; it simply holds the carbon dioxide in bubbles long enough to see.", 'item_emoji' => "🧴", 'action_label' => "Add detergent"],
                    ['step_order' => 4, 'title' => "Pour the vinegar", 'instruction_text' => "Pour the ethanoic acid in steadily and step back. Carbon dioxide forms at once.", 'preauthored_guidance' => "Pour in one steady movement rather than a trickle, and keep your face away from the crater. The foam carries no heat but it does carry liquid.", 'item_emoji' => "🍶", 'action_label' => "Pour vinegar"],
                ],
                'quiz' => [
                    ['prompt' => "Which gas makes the volcano foam?", 'options' => ["Carbon dioxide", "Oxygen", "Hydrogen", "Nitrogen"], 'correct_option' => 0, 'explanation' => "Carbon dioxide is released when the hydrogen carbonate reacts with the acid. It is the gas that turns limewater milky."],
                    ['prompt' => "In this reaction, baking soda acts as:", 'options' => ["A base", "An acid", "A catalyst", "An indicator"], 'correct_option' => 0, 'explanation' => "Sodium hydrogen carbonate accepts a proton from the ethanoic acid, which is what makes it the base in this pair."],
                    ['prompt' => "Why is detergent added?", 'options' => ["To trap the gas as visible foam", "To speed up the reaction", "To neutralise the acid", "To colour the mixture"], 'correct_option' => 0, 'explanation' => "The detergent is not a reactant. It lowers the surface tension so the escaping gas forms a lasting foam."],
                    ['prompt' => "The salt produced in this reaction is:", 'options' => ["Sodium ethanoate", "Sodium chloride", "Calcium carbonate", "Sodium hydroxide"], 'correct_option' => 0, 'explanation' => "The acid gives ethanoate and the base gives sodium, so the salt is sodium ethanoate."],
                ],
            ],
            [
                'slug' => "chromatography",
                'title' => "Paper Chromatography",
                'topic' => "Mixtures",
                'difficulty' => "easy",
                'grade_level' => 9,
                'prerequisite_slug' => null,
                'chemical_equation' => "No chemical change. Separation by differing solubility and adsorption: Rf = distance moved by component ÷ distance moved by solvent",
                'explanatory_note' => "Chromatography separates a mixture without changing any substance in it. A dye that dissolves readily in the solvent and sticks weakly to the paper travels far; one that clings to the paper stays near the start. Each component has its own Rf value under the same conditions.",
                'ar_available' => false,
                'base_points' => 40,
                'estimated_minutes' => 15,
                'emoji' => "🌿",
                'card_gradient' => "linear-gradient(135deg,#E8F5E9,#A5D6A7)",
                'steps' => [
                    ['step_order' => 1, 'title' => "Draw the baseline", 'instruction_text' => "Draw a pencil line 2 cm from the bottom edge of the filter paper and mark a spot of black ink on it.", 'preauthored_guidance' => "The baseline is drawn in pencil, never in ink. Pencil graphite does not dissolve, so it cannot travel up the paper and confuse the result.", 'item_emoji' => "✏️", 'action_label' => "Draw baseline"],
                    ['step_order' => 2, 'title' => "Stand the paper in solvent", 'instruction_text' => "Place the paper so the solvent is below the ink spot, never touching it.", 'preauthored_guidance' => "If the solvent reaches the spot directly, the ink washes into the liquid instead of climbing the paper, and nothing separates.", 'item_emoji' => "💧", 'action_label' => "Dip paper"],
                    ['step_order' => 3, 'title' => "Let the solvent rise", 'instruction_text' => "Wait while the solvent climbs through the ink and carries the dyes with it.", 'preauthored_guidance' => "Leave it undisturbed. Moving the container tilts the solvent front and the components no longer line up for measurement.", 'item_emoji' => "⏳", 'action_label' => "Wait"],
                    ['step_order' => 4, 'title' => "Measure the Rf values", 'instruction_text' => "Mark the solvent front, then measure each colour from the baseline and divide by the solvent distance.", 'preauthored_guidance' => "Measure to the centre of each coloured band, and record the solvent front before the paper dries, because it becomes invisible afterwards.", 'item_emoji' => "📏", 'action_label' => "Measure"],
                ],
                'quiz' => [
                    ['prompt' => "Why is the baseline drawn in pencil?", 'options' => ["Pencil does not dissolve in the solvent", "Pencil is easier to see", "Ink would be too thick", "Pencil repels water"], 'correct_option' => 0, 'explanation' => "Ink would separate and travel up the paper along with the sample, making the result unreadable."],
                    ['prompt' => "A dye that travels furthest up the paper is:", 'options' => ["Most soluble in the solvent", "Least soluble in the solvent", "The heaviest", "The darkest"], 'correct_option' => 0, 'explanation' => "The further a component travels, the more it favours the moving solvent over the paper."],
                    ['prompt' => "Rf is calculated as:", 'options' => ["Distance moved by the component ÷ distance moved by the solvent", "Distance moved by the solvent ÷ distance moved by the component", "Component distance × solvent distance", "Component distance + solvent distance"], 'correct_option' => 0, 'explanation' => "Rf is a ratio and so has no units. It is constant for a given substance, solvent and paper."],
                    ['prompt' => "Chromatography separates a mixture because the components differ in:", 'options' => ["Solubility and adsorption", "Colour only", "Melting point", "Density"], 'correct_option' => 0, 'explanation' => "Separation depends on how each component divides itself between the moving solvent and the stationary paper."],
                ],
            ],
            [
                'slug' => "blue-bottle",
                'title' => "Blue Bottle Reaction",
                'topic' => "Reactions",
                'difficulty' => "medium",
                'grade_level' => 10,
                'prerequisite_slug' => "volcano",
                'chemical_equation' => "Shaking: leuco-methylene blue + O₂ → methylene blue (blue) ; Standing: methylene blue + glucose → leuco-methylene blue (colourless)",
                'explanatory_note' => "A redox cycle you can watch. Shaking dissolves oxygen, which oxidises the colourless leuco form of methylene blue back to its blue form. On standing, glucose in alkaline solution reduces it to colourless again. The dye is a catalyst for the oxidation of glucose and is not consumed, so the cycle repeats many times.",
                'ar_available' => true,
                'base_points' => 80,
                'estimated_minutes' => 15,
                'emoji' => "🔵",
                'card_gradient' => "linear-gradient(135deg,#E3F2FD,#90CAF9)",
                'steps' => [
                    ['step_order' => 1, 'title' => "Prepare the alkaline glucose solution", 'instruction_text' => "Dissolve glucose in water, then add the sodium hydroxide solution.", 'preauthored_guidance' => "Sodium hydroxide is corrosive. Goggles and gloves are worn, and the alkali is added to the glucose solution rather than the other way about.", 'item_emoji' => "🧪", 'action_label' => "Mix solution"],
                    ['step_order' => 2, 'title' => "Add the methylene blue", 'instruction_text' => "Add two drops of methylene blue. The solution turns blue, then fades as the glucose reduces it.", 'preauthored_guidance' => "Two drops is plenty. Too much dye takes several minutes to fade and the cycle becomes tedious to watch.", 'item_emoji' => "💧", 'action_label' => "Add indicator"],
                    ['step_order' => 3, 'title' => "Shake the bottle", 'instruction_text' => "Stopper the bottle and shake. Dissolved oxygen oxidises the dye and the blue colour returns.", 'preauthored_guidance' => "Hold the stopper firmly while shaking. The colour appears within a second or two of the oxygen dissolving.", 'item_emoji' => "🫨", 'action_label' => "Shake bottle"],
                    ['step_order' => 4, 'title' => "Let it stand and repeat", 'instruction_text' => "Set the bottle down. The blue fades as glucose reduces the dye again, and the cycle can be repeated.", 'preauthored_guidance' => "Count the seconds until the colour goes. The fade becomes slower each time, as the glucose is gradually used up.", 'item_emoji' => "🔄", 'action_label' => "Observe cycle"],
                ],
                'quiz' => [
                    ['prompt' => "Shaking the bottle turns the solution blue because it:", 'options' => ["Dissolves oxygen, which oxidises the dye", "Warms the solution", "Mixes in more glucose", "Breaks down the dye"], 'correct_option' => 0, 'explanation' => "Shaking increases the surface in contact with air, so oxygen dissolves and oxidises the leuco form back to blue."],
                    ['prompt' => "On standing, the colour fades because glucose:", 'options' => ["Reduces the dye", "Oxidises the dye", "Evaporates", "Neutralises the alkali"], 'correct_option' => 0, 'explanation' => "Glucose is the reducing agent. In alkaline solution it hands electrons to the dye, returning it to its colourless form."],
                    ['prompt' => "The role of methylene blue in the overall reaction is as a:", 'options' => ["Catalyst for the oxidation of glucose", "Reactant that is used up", "Base", "Solvent"], 'correct_option' => 0, 'explanation' => "The dye is regenerated in every cycle, which is why the reaction can be repeated many times with the same two drops."],
                    ['prompt' => "Why must sodium hydroxide be handled with gloves and goggles?", 'options' => ["It is corrosive to skin and eyes", "It is flammable", "It stains clothing", "It smells strongly"], 'correct_option' => 0, 'explanation' => "Sodium hydroxide destroys living tissue on contact, and the eyes above all."],
                ],
            ],
            [
                'slug' => "crystal-growing",
                'title' => "Crystal Growing",
                'topic' => "Solutions",
                'difficulty' => "medium",
                'grade_level' => 9,
                'prerequisite_slug' => null,
                'chemical_equation' => "Saturated solution on cooling: solute(aq) ⇌ solute(s) + heat (crystallisation)",
                'explanatory_note' => "Solubility rises with temperature. A solution saturated while hot holds more solute than it can keep when cool, so the excess leaves the solution as a solid. Slow cooling gives large, well-formed crystals because particles have time to join the growing lattice in order; rapid cooling gives many small ones.",
                'ar_available' => false,
                'base_points' => 60,
                'estimated_minutes' => 30,
                'emoji' => "🧊",
                'card_gradient' => "linear-gradient(135deg,#FCE4EC,#F8BBD9)",
                'steps' => [
                    ['step_order' => 1, 'title' => "Make a saturated solution", 'instruction_text' => "Add the solute to hot water and keep stirring until no more will dissolve.", 'preauthored_guidance' => "Keep adding until a little solid remains on the bottom that will not dissolve. That is how you know the solution is saturated at this temperature.", 'item_emoji' => "♨️", 'action_label' => "Saturate"],
                    ['step_order' => 2, 'title' => "Filter the hot solution", 'instruction_text' => "Filter the solution while hot so that no undissolved grains remain.", 'preauthored_guidance' => "Undissolved grains act as extra starting points. Filtering them out is what gives you one large crystal instead of a cloud of small ones.", 'item_emoji' => "⚗️", 'action_label' => "Filter"],
                    ['step_order' => 3, 'title' => "Suspend a seed crystal", 'instruction_text' => "Hang a seed crystal on a thread so it sits in the solution without touching the sides.", 'preauthored_guidance' => "The crystal must hang free. Touching the glass gives the lattice a second surface to build on and the shape comes out distorted.", 'item_emoji' => "🧵", 'action_label' => "Suspend seed"],
                    ['step_order' => 4, 'title' => "Cool slowly and observe", 'instruction_text' => "Leave the container undisturbed in a cool place and record the growth each day.", 'preauthored_guidance' => "Do not move or stir it. Every disturbance starts new crystals, and the one you are growing stops getting the solute.", 'item_emoji' => "💎", 'action_label' => "Observe growth"],
                ],
                'quiz' => [
                    ['prompt' => "A saturated solution is one that:", 'options' => ["Holds the maximum solute that will dissolve at that temperature", "Contains no solute", "Is always hot", "Has solid at the bottom"], 'correct_option' => 0, 'explanation' => "Saturation is defined at a stated temperature, because solubility changes with temperature."],
                    ['prompt' => "Slow cooling produces:", 'options' => ["Fewer, larger crystals", "Many tiny crystals", "No crystals", "A gas"], 'correct_option' => 0, 'explanation' => "Slow cooling gives particles time to join an existing lattice in order rather than forming many new centres."],
                    ['prompt' => "Crystals form on cooling because solubility:", 'options' => ["Decreases as temperature falls", "Increases as temperature falls", "Stays constant", "Depends only on stirring"], 'correct_option' => 0, 'explanation' => "The cooler solution cannot hold what the hot one held, so the excess solute leaves solution as solid."],
                    ['prompt' => "The hot solution is filtered in order to:", 'options' => ["Remove undissolved grains that would seed small crystals", "Cool it faster", "Add air", "Remove the colour"], 'correct_option' => 0, 'explanation' => "Each stray grain is a site where a new crystal starts, which competes with the seed you intend to grow."],
                ],
            ],
            [
                'slug' => "ph-indicators",
                'title' => "pH Indicators from Red Cabbage",
                'topic' => "Acids and Bases",
                'difficulty' => "medium",
                'grade_level' => 10,
                'prerequisite_slug' => "volcano",
                'chemical_equation' => "Anthocyanin (HIn) ⇌ H⁺ + In⁻ : HIn red in acid, In⁻ blue to green in alkali",
                'explanatory_note' => "Anthocyanin from red cabbage is a weak acid whose protonated and deprotonated forms have different colours. Adding acid shifts the equilibrium left and the solution turns red or pink; adding alkali shifts it right and the colour moves through purple to blue and green. The colour therefore reports the hydrogen ion concentration.",
                'ar_available' => false,
                'base_points' => 70,
                'estimated_minutes' => 20,
                'emoji' => "⚗️",
                'card_gradient' => "linear-gradient(135deg,#F3E5F5,#CE93D8)",
                'steps' => [
                    ['step_order' => 1, 'title' => "Extract the indicator", 'instruction_text' => "Shred red cabbage, cover with hot water and leave it to stand until the liquid is deep purple.", 'preauthored_guidance' => "Hot water from a kettle is enough; the cabbage does not need to be boiled on a flame. Deep purple means enough anthocyanin has been extracted.", 'item_emoji' => "🥬", 'action_label' => "Extract"],
                    ['step_order' => 2, 'title' => "Set out the test solutions", 'instruction_text' => "Place equal volumes of your test liquids in a row of labelled tubes.", 'preauthored_guidance' => "Equal volumes matter. A different volume changes how far the colour shifts and the comparison across the row stops being fair.", 'item_emoji' => "🧫", 'action_label' => "Prepare tubes"],
                    ['step_order' => 3, 'title' => "Add the indicator", 'instruction_text' => "Add the same number of drops of cabbage extract to each tube and note the colour.", 'preauthored_guidance' => "The same number of drops in every tube. Comparing colours only works when the amount of indicator is the same.", 'item_emoji' => "💧", 'action_label' => "Add indicator"],
                    ['step_order' => 4, 'title' => "Build the colour scale", 'instruction_text' => "Order the tubes from red to green and match each one to its approximate pH.", 'preauthored_guidance' => "Record the colours as you see them, not as you expect them. A result that disagrees with your prediction is still the result.", 'item_emoji' => "📊", 'action_label' => "Record scale"],
                ],
                'quiz' => [
                    ['prompt' => "Red cabbage extract turns red in:", 'options' => ["Acidic solution", "Alkaline solution", "Neutral solution", "Any solution"], 'correct_option' => 0, 'explanation' => "In acid the protonated form HIn predominates, and that form is red."],
                    ['prompt' => "The extract works as an indicator because the two forms of anthocyanin:", 'options' => ["Have different colours", "Have the same colour", "Are both acids", "Do not react"], 'correct_option' => 0, 'explanation' => "The colour you see depends on which form the equilibrium favours, and so on the hydrogen ion concentration."],
                    ['prompt' => "A solution that turns the extract green is:", 'options' => ["Strongly alkaline", "Strongly acidic", "Neutral", "Saturated"], 'correct_option' => 0, 'explanation' => "Green appears at the high pH end of the scale, where the deprotonated form predominates."],
                    ['prompt' => "Adding acid to the extract shifts the equilibrium:", 'options' => ["Towards HIn, the red form", "Towards In⁻, the blue form", "Nowhere", "Towards water"], 'correct_option' => 0, 'explanation' => "Extra hydrogen ions push the equilibrium back towards the protonated red form, as Le Chatelier predicts."],
                ],
            ],
            [
                'slug' => "electrolysis-water",
                'title' => "Electrolysis of Water",
                'topic' => "Electrochemistry",
                'difficulty' => "hard",
                'grade_level' => 10,
                'prerequisite_slug' => "blue-bottle",
                'chemical_equation' => "Overall: 2H₂O(l) → 2H₂(g) + O₂(g) ; Cathode: 4H₂O + 4e⁻ → 2H₂ + 4OH⁻ ; Anode: 2H₂O → O₂ + 4H⁺ + 4e⁻",
                'explanatory_note' => "A direct current decomposes water into its elements. Reduction at the cathode gives hydrogen and oxidation at the anode gives oxygen, in a volume ratio of two to one because each water molecule carries twice as much hydrogen as oxygen. Pure water conducts poorly, so a little sulfuric acid or sodium sulfate is added as electrolyte; the added ions are not themselves discharged.",
                'ar_available' => true,
                'base_points' => 100,
                'estimated_minutes' => 25,
                'emoji' => "🌊",
                'card_gradient' => "linear-gradient(135deg,#E0F7FA,#80DEEA)",
                'steps' => [
                    ['step_order' => 1, 'title' => "Set up the cell", 'instruction_text' => "Fill the cell with water, add a little electrolyte and place the two inert electrodes.", 'preauthored_guidance' => "Carbon or platinum electrodes are used because they do not take part in the reaction. A reactive metal electrode would corrode instead.", 'item_emoji' => "🔌", 'action_label' => "Set up cell"],
                    ['step_order' => 2, 'title' => "Invert a tube over each electrode", 'instruction_text' => "Fill two tubes with the solution and invert one over each electrode to collect the gases.", 'preauthored_guidance' => "Each tube must be full of solution before it goes over the electrode, otherwise trapped air is mistaken for collected gas.", 'item_emoji' => "🧪", 'action_label' => "Place tubes"],
                    ['step_order' => 3, 'title' => "Pass the current", 'instruction_text' => "Connect the supply and watch bubbles form at both electrodes.", 'preauthored_guidance' => "Keep the voltage low and the supply direct current. Alternating current would reverse the electrodes many times a second and collect nothing.", 'item_emoji' => "⚡", 'action_label' => "Switch on"],
                    ['step_order' => 4, 'title' => "Compare the volumes", 'instruction_text' => "Record the volume in each tube. Hydrogen at the cathode is about twice the oxygen at the anode.", 'preauthored_guidance' => "Read the volumes at the same moment and at eye level. Oxygen is a little more soluble in water, so the ratio comes out slightly above two to one.", 'item_emoji' => "📐", 'action_label' => "Measure gases"],
                ],
                'quiz' => [
                    ['prompt' => "Which gas collects at the cathode?", 'options' => ["Hydrogen", "Oxygen", "Chlorine", "Carbon dioxide"], 'correct_option' => 0, 'explanation' => "The cathode is negative, so it attracts the positive hydrogen species and reduction produces hydrogen there."],
                    ['prompt' => "The ratio of hydrogen to oxygen collected is about:", 'options' => ["2 to 1", "1 to 2", "1 to 1", "4 to 1"], 'correct_option' => 0, 'explanation' => "Each water molecule holds two hydrogen atoms for one oxygen, and the collected volumes follow that ratio."],
                    ['prompt' => "Why is an electrolyte added to the water?", 'options' => ["Pure water conducts electricity poorly", "It produces the hydrogen", "It colours the solution", "It prevents heating"], 'correct_option' => 0, 'explanation' => "The added ions carry charge through the solution. They are not discharged themselves at these voltages."],
                    ['prompt' => "The reaction at the anode is:", 'options' => ["Oxidation", "Reduction", "Neutralisation", "Precipitation"], 'correct_option' => 0, 'explanation' => "Electrons are lost at the anode, which is oxidation, and oxygen is released."],
                ],
            ],
        ];

        // First pass creates every experiment, second pass links prerequisites,
        // so the order of this array does not matter (FR-03.3).
        foreach ($experiments as $data) {
            $experiment = Experiment::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'title' => $data['title'],
                    'topic' => $data['topic'],
                    'difficulty' => $data['difficulty'],
                    'grade_level' => $data['grade_level'],
                    'chemical_equation' => $data['chemical_equation'],
                    'explanatory_note' => $data['explanatory_note'],
                    'ar_available' => $data['ar_available'],
                    'base_points' => $data['base_points'],
                    'estimated_minutes' => $data['estimated_minutes'],
                    'emoji' => $data['emoji'],
                    'card_gradient' => $data['card_gradient'],
                    'is_published' => true,
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

        foreach ($experiments as $data) {
            if ($data['prerequisite_slug']) {
                Experiment::where('slug', $data['slug'])->update([
                    'prerequisite_experiment_id' => Experiment::where('slug', $data['prerequisite_slug'])->value('id'),
                ]);
            }
        }
    }
}
