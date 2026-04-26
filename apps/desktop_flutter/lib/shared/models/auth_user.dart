class AuthUser {
  const AuthUser({
    required this.id,
    required this.name,
    required this.email,
    required this.roles,
    required this.permissions,
  });

  final int id;
  final String name;
  final String email;
  final List<String> roles;
  final List<String> permissions;

  bool get isCashier => roles.contains('cashier') || roles.contains('admin');
  bool get isOwner => roles.contains('owner') || roles.contains('admin');
  bool get isViewer => roles.contains('viewer');

  bool hasRole(String role) => roles.contains(role);

  bool can(String permission) =>
      roles.contains('owner') ||
      roles.contains('admin') ||
      permissions.contains(permission);

  factory AuthUser.fromJson(Map<String, dynamic> json) {
    return AuthUser(
      id: (json['id'] as num?)?.toInt() ?? 0,
      name: json['name']?.toString() ?? '-',
      email: json['email']?.toString() ?? '-',
      roles: ((json['roles'] as List?) ?? <dynamic>[])
          .map((item) => item.toString())
          .toList(),
      permissions: ((json['permissions'] as List?) ?? <dynamic>[])
          .map((item) => item.toString())
          .toList(),
    );
  }

  Map<String, dynamic> toJson() {
    return <String, dynamic>{
      'id': id,
      'name': name,
      'email': email,
      'roles': roles,
      'permissions': permissions,
    };
  }
}
