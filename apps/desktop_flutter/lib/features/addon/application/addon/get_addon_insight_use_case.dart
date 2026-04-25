// lib/application/addon/get_addon_insight_use_case.dart

import '../../domain/entities/addon_insight.dart';
import '../../domain/repositories/addon_repository.dart';

class GetAddonInsightUseCase {
  final AddOnRepository repository;
  GetAddonInsightUseCase(this.repository);

  Future<AddonInsight> execute() => repository.getInsight();
}
