// lib/domain/repositories/addon_repository.dart

import '../entities/add_on.dart';
import '../entities/addon_insight.dart';

abstract class AddOnRepository {
  Future<List<AddOn>> getAddOnList();
  Future<AddonInsight> getInsight();
}
