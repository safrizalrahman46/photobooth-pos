class BranchOption {
  const BranchOption({required this.id, required this.name});

  final int id;
  final String name;

  factory BranchOption.fromJson(Map<String, dynamic> json) {
    return BranchOption(
      id: (json['id'] as num?)?.toInt() ?? 0,
      name: json['name']?.toString() ?? '-',
    );
  }
}
