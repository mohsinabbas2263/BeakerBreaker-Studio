import 'package:beaker_breakers/game/lab_items.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('every item id has an icon and a label, and unknown ids read as words', () {
    expect(item('goggles').label, 'Goggles');
    expect(item('mystery_flask').label, 'Mystery flask');
    expect(place('tube_mg'), 'Tube: Mg');
  });
}
