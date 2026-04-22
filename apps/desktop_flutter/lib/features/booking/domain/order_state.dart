class OrderState {
  final String customerName;
  final String phone;
  final int pax;

  final String? selectedPackage;
  final int packagePrice;

  final Map<String, int> addons; // name : qty

  final int total;

  OrderState({
    this.customerName = "",
    this.phone = "",
    this.pax = 1,
    this.selectedPackage,
    this.packagePrice = 0,
    this.addons = const {},
    this.total = 0,
  });

  OrderState copyWith({
    String? customerName,
    String? phone,
    int? pax,
    String? selectedPackage,
    int? packagePrice,
    Map<String, int>? addons,
    int? total,
  }) {
    return OrderState(
      customerName: customerName ?? this.customerName,
      phone: phone ?? this.phone,
      pax: pax ?? this.pax,
      selectedPackage: selectedPackage ?? this.selectedPackage,
      packagePrice: packagePrice ?? this.packagePrice,
      addons: addons ?? this.addons,
      total: total ?? this.total,
    );
  }
}
