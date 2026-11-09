// GENERATED from web/lib/labItems.ts by the brand/items script; edit that file, not this one.
// docs/experiment-engine.md: every item id maps to one icon and label in every client.

import 'package:flutter/material.dart';

class LabItem {
  const LabItem(this.emoji, this.label);
  final String emoji;
  final String label;
}

const Map<String, LabItem> kItems = {
  'goggles': LabItem('🥽', 'Goggles'),
  'coat': LabItem('🥼', 'Lab coat'),
  'gloves': LabItem('🧤', 'Gloves'),
  'sandals': LabItem('🩴', 'Sandals'),
  'snack': LabItem('🍪', 'Snack'),
  'beaker': LabItem('🥛', 'Beaker'),
  'test_tube': LabItem('🧪', 'Test tube'),
  'conical_flask': LabItem('⚗️', 'Conical flask'),
  'flask': LabItem('⚗️', 'Flask'),
  'burette': LabItem('📏', 'Burette'),
  'glass_tube': LabItem('🧫', 'Long glass tube'),
  'gas_syringe': LabItem('💉', 'Gas syringe'),
  'funnel': LabItem('🔻', 'Funnel'),
  'filter_paper': LabItem('📄', 'Filter paper'),
  'glass_rod': LabItem('🥢', 'Glass rod'),
  'thermometer': LabItem('🌡️', 'Thermometer'),
  'fractionating_column': LabItem('🏛️', 'Fractionating column'),
  'tweezers': LabItem('🪡', 'Tweezers'),
  'pencil': LabItem('✏️', 'Pencil'),
  'volumetric_flask': LabItem('🍶', 'Volumetric flask'),
  'graduated_pipette': LabItem('💉', 'Graduated pipette'),
  'pipette': LabItem('💉', 'Pipette'),
  'evaporating_dish': LabItem('🥣', 'Evaporating dish'),
  'sandpaper': LabItem('🟫', 'Sandpaper'),
  'ice_cubes': LabItem('🧊', 'Ice cubes'),
  'sugar': LabItem('🍬', 'Sugar'),
  'copper_oxide': LabItem('⚫', 'Copper(II) oxide'),
  'magnesium': LabItem('🎗️', 'Magnesium'),
  'iron': LabItem('🔩', 'Iron'),
  'conc_hcl': LabItem('☠️', 'Concentrated HCl'),
  'cotton_nh3': LabItem('☁️', 'Cotton with ammonia'),
  'cotton_hcl': LabItem('☁️', 'Cotton with HCl'),
  'glowing_splint': LabItem('🪵', 'Glowing splint'),
  'burning_splint': LabItem('🔥', 'Burning splint'),
  'anhydrous_cuso4': LabItem('⚪', 'Anhydrous copper sulphate'),
  'hydrated_cuso4': LabItem('🔷', 'Hydrated copper sulphate'),
  'sodium_chloride': LabItem('🧂', 'Sodium chloride'),
  'sodium_carbonate': LabItem('🧂', 'Sodium carbonate'),
  'copper': LabItem('🟤', 'Copper'),
  'zinc': LabItem('⚙️', 'Zinc'),
  'limestone': LabItem('🪨', 'Limestone chips'),
  'sand': LabItem('🏖️', 'Sand'),
  'butter': LabItem('🧈', 'Butter'),
  'aluminium_powder': LabItem('✨', 'Aluminium powder'),
  'red_litmus': LabItem('🟥', 'Red litmus'),
  'blue_litmus': LabItem('🟦', 'Blue litmus'),
  'damp_red_litmus': LabItem('🟥', 'Damp red litmus'),
  'damp_blue_litmus': LabItem('🟦', 'Damp blue litmus'),
  'methyl_orange': LabItem('🟠', 'Methyl orange'),
  'phenolphthalein': LabItem('🩷', 'Phenolphthalein'),
  'starch': LabItem('🍚', 'Starch solution'),
  'water': LabItem('💧', 'Water'),
  'distilled_water': LabItem('💧', 'Distilled water'),
  'lime_water': LabItem('🥛', 'Lime water'),
  'naoh': LabItem('🧴', 'Sodium hydroxide'),
  'dilute_hcl': LabItem('🧴', 'Dilute HCl'),
  'dilute_h2so4': LabItem('🧴', 'Dilute sulphuric acid'),
  'h2o2': LabItem('🫧', 'Hydrogen peroxide'),
  'acidified_kmno4': LabItem('🟣', 'Acidified KMnO₄'),
  'kmno4': LabItem('🟣', 'Potassium permanganate'),
  'vegetable_oil': LabItem('🫒', 'Vegetable oil'),
  'ethanol_water': LabItem('🍶', 'Ethanol–water mixture'),
  'silver_nitrate': LabItem('🥈', 'Silver nitrate'),
  'barium_nitrate': LabItem('🧴', 'Barium nitrate'),
  'aqueous_ammonia': LabItem('🧴', 'Aqueous ammonia'),
  'acetic_anhydride': LabItem('🧴', 'Acetic anhydride'),
  'mnso4': LabItem('🧴', 'Manganese(II) sulphate'),
  'alkaline_iodide_azide': LabItem('🧴', 'Alkaline iodide azide'),
  'dnph': LabItem('🟠', '2,4-DNPH'),
  'fehling_solution': LabItem('🔵', 'Fehling\'s solution'),
  'tollens_reagent': LabItem('🪞', 'Tollens\' reagent'),
  'ink_sample': LabItem('🖋️', 'Ink'),
  'ink_crime_scene': LabItem('📝', 'Crime scene ink'),
  'ink_suspect_a': LabItem('🖊️', 'Suspect A\'s pen'),
  'ink_suspect_b': LabItem('🖊️', 'Suspect B\'s pen'),
};

const Map<String, String> kPlaces = {
  'stand': 'Clamp stand',
  'under': 'Under the burette',
  'in_funnel': 'Inside the funnel',
  'on_flask': 'On the flask',
  'column_top': 'Top of the column',
  'left_end': 'Left end',
  'right_end': 'Right end',
  'tube_a': 'Tube A',
  'tube_b': 'Tube B',
  'lane_1': 'Lane 1',
  'lane_2': 'Lane 2',
  'lane_3': 'Lane 3',
  'first': 'Add first',
  'second': 'Add second',
  'chromatography_paper': 'Chromatography paper',
  'sodium_metal': 'Sodium',
  'gas_tube': 'Gas',
  'tray': 'Tray',
  'tripod': 'Tripod stand',
  'volumetric_flask': 'Volumetric flask',
  'conc_hcl': 'Concentrated HCl',
  'tube_mg': 'Tube: Mg',
  'tube_zn': 'Tube: Zn',
  'tube_fe': 'Tube: Fe',
  'tube_cu': 'Tube: Cu',
};

const Map<String, Color> kColours = {
  'pink': Color(0xFFF48FB1),
  'colourless': Color(0xFFF5FBFF),
  'pale_green': Color(0xFFC5E8C9),
  'pale_purple': Color(0xFFCE93D8),
  'yellow_brown': Color(0xFFC9A227),
  'pale_yellow': Color(0xFFFFF59D),
  'blue': Color(0xFF4FC3F7),
  'yellow': Color(0xFFFFD54F),
  'orange': Color(0xFFFF8A50),
  'bubbling': Color(0xFFE8F4FD),
  'fizzing': Color(0xFFE8F4FD),
  'zinc_settles': Color(0xFFCFD8DC),
  'solid_left': Color(0xFFECEFF1),
};

String _words(String id) {
  final s = id.replaceAll('_', ' ');
  return s.isEmpty ? s : s[0].toUpperCase() + s.substring(1);
}

LabItem item(String id) => kItems[id] ?? LabItem('🧪', _words(id));
String place(String id) => kPlaces[id] ?? _words(id);
Color colour(String name) => kColours[name] ?? const Color(0xFFE8F4FD);
