// lib/application/addon/get_addon_list_use_case.dart

import '../../domain/entities/add_on.dart';
import '../../domain/repositories/addon_repository.dart';

class GetAddOnListUseCase {
  final AddOnRepository repository;
  GetAddOnListUseCase(this.repository);

  Future<List<AddOn>> execute() => repository.getAddOnList();
}
