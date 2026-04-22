import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../domain/order_state.dart';

final orderProvider = StateNotifierProvider<OrderNotifier, OrderState>((ref) {
  return OrderNotifier();
});

class OrderNotifier extends StateNotifier<OrderState> {
  OrderNotifier() : super(OrderState());

  void setCustomer(String name, String phone) {
    state = state.copyWith(customerName: name, phone: phone);
  }

  void selectPackage(String name, int price) {
    final total = price + _addonTotal(state.addons);
    state = state.copyWith(
      selectedPackage: name,
      packagePrice: price,
      total: total,
    );
  }

  void addAddon(String name, int price) {
    final newAddons = Map<String, int>.from(state.addons);
    newAddons[name] = (newAddons[name] ?? 0) + 1;

    final total = state.packagePrice + _addonTotal(newAddons);

    state = state.copyWith(addons: newAddons, total: total);
  }

  void removeAddon(String name, int price) {
    final newAddons = Map<String, int>.from(state.addons);

    if (!newAddons.containsKey(name)) return;

    if (newAddons[name]! <= 1) {
      newAddons.remove(name);
    } else {
      newAddons[name] = newAddons[name]! - 1;
    }

    final total = state.packagePrice + _addonTotal(newAddons);

    state = state.copyWith(addons: newAddons, total: total);
  }

  int _addonTotal(Map<String, int> addons) {
    int total = 0;

    addons.forEach((key, qty) {
      // sementara harga dummy 5000
      total += qty * 5000;
    });

    return total;
  }

  void reset() {
    state = OrderState();
  }
}
