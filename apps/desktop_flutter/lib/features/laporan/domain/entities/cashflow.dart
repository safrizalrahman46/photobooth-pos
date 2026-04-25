class Cashflow {
  final double kasMasuk;
  final double kasKeluar;

  const Cashflow({required this.kasMasuk, required this.kasKeluar});

  double get saldo => kasMasuk - kasKeluar;

  String get status => saldo >= 0 ? "Surplus" : "Defisit";
}
