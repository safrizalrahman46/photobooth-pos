class LaporanSummary {
  final double totalPendapatan;
  final double percentageChange;
  final int jumlahBooking;
  final String paketTerlaris;
  final int pesananTerkonfirmasi;

  const LaporanSummary({
    required this.totalPendapatan,
    required this.percentageChange,
    required this.jumlahBooking,
    required this.paketTerlaris,
    required this.pesananTerkonfirmasi,
  });
}
