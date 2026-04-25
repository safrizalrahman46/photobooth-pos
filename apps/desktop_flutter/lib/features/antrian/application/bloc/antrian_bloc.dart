// application/bloc/antrian_bloc.dart

import 'dart:async';
import 'package:flutter/foundation.dart';
import '../../domain/entities/antrian_entity.dart';
import '../../domain/entities/booth_entity.dart';
import '../usecase/antrian_usecase.dart';

// ─── EVENTS ───────────────────────────────────────────────────────────────────
abstract class AntrianEvent {}

class LoadAntrianEvent extends AntrianEvent {}

class PindahKeSelesaiEvent extends AntrianEvent {
  final String antrianId;
  final String catatan;
  PindahKeSelesaiEvent(this.antrianId, this.catatan);
}

class SetBoothReadyEvent extends AntrianEvent {
  final String boothId;
  SetBoothReadyEvent(this.boothId);
}

class _AntrianMenungguUpdated extends AntrianEvent {
  final List<AntrianEntity> antrian;
  _AntrianMenungguUpdated(this.antrian);
}

class _BoothUpdated extends AntrianEvent {
  final List<BoothEntity> booths;
  _BoothUpdated(this.booths);
}

// ─── STATE ────────────────────────────────────────────────────────────────────
class AntrianState {
  final bool isLoading;
  final List<AntrianEntity> antrianMenunggu;
  final List<AntrianEntity> antrianSelesai;
  final List<BoothEntity> booths;
  final AntrianEntity? antrianBerikutnya;
  final String? errorMessage;

  const AntrianState({
    this.isLoading = false,
    this.antrianMenunggu = const [],
    this.antrianSelesai = const [],
    this.booths = const [],
    this.antrianBerikutnya,
    this.errorMessage,
  });

  AntrianState copyWith({
    bool? isLoading,
    List<AntrianEntity>? antrianMenunggu,
    List<AntrianEntity>? antrianSelesai,
    List<BoothEntity>? booths,
    AntrianEntity? antrianBerikutnya,
    String? errorMessage,
  }) {
    return AntrianState(
      isLoading: isLoading ?? this.isLoading,
      antrianMenunggu: antrianMenunggu ?? this.antrianMenunggu,
      antrianSelesai: antrianSelesai ?? this.antrianSelesai,
      booths: booths ?? this.booths,
      antrianBerikutnya: antrianBerikutnya ?? this.antrianBerikutnya,
      errorMessage: errorMessage ?? this.errorMessage,
    );
  }
}

// ─── BLOC ─────────────────────────────────────────────────────────────────────
class AntrianBloc extends ChangeNotifier {
  final GetAntrianMenungguUseCase _getAntrianMenunggu;
  final GetAntrianSelesaiUseCase _getAntrianSelesai;
  final GetAntrianBerikutnyaUseCase _getAntrianBerikutnya;
  final GetAllBoothUseCase _getAllBooth;
  final PindahKeSelesaiUseCase _pindahKeSelesai;
  final SetBoothReadyUseCase _setBoothReady;

  StreamSubscription? _antrianSub;
  StreamSubscription? _boothSub;

  AntrianState _state = const AntrianState();
  AntrianState get state => _state;

  AntrianBloc({
    required GetAntrianMenungguUseCase getAntrianMenunggu,
    required GetAntrianSelesaiUseCase getAntrianSelesai,
    required GetAntrianBerikutnyaUseCase getAntrianBerikutnya,
    required GetAllBoothUseCase getAllBooth,
    required PindahKeSelesaiUseCase pindahKeSelesai,
    required SetBoothReadyUseCase setBoothReady,
  })  : _getAntrianMenunggu = getAntrianMenunggu,
        _getAntrianSelesai = getAntrianSelesai,
        _getAntrianBerikutnya = getAntrianBerikutnya,
        _getAllBooth = getAllBooth,
        _pindahKeSelesai = pindahKeSelesai,
        _setBoothReady = setBoothReady {
    _init();
  }

  void _emit(AntrianState newState) {
    _state = newState;
    notifyListeners();
  }

  Future<void> _init() async {
    _emit(_state.copyWith(isLoading: true));
    try {
      final selesai = await _getAntrianSelesai();
      final berikutnya = await _getAntrianBerikutnya();
      _emit(_state.copyWith(
        isLoading: false,
        antrianSelesai: selesai,
        antrianBerikutnya: berikutnya,
      ));

      _antrianSub = _getAntrianMenunggu.watch().listen((list) {
        _emit(_state.copyWith(antrianMenunggu: list));
      });
      _boothSub = _getAllBooth.watch().listen((list) {
        _emit(_state.copyWith(booths: list));
      });
    } catch (e) {
      _emit(_state.copyWith(isLoading: false, errorMessage: e.toString()));
    }
  }

  Future<void> pindahKeSelesai(String antrianId, String catatan) async {
    try {
      await _pindahKeSelesai(antrianId, catatan);
      final selesai = await _getAntrianSelesai();
      final berikutnya = await _getAntrianBerikutnya();
      _emit(_state.copyWith(
        antrianSelesai: selesai,
        antrianBerikutnya: berikutnya,
      ));
    } catch (e) {
      _emit(_state.copyWith(errorMessage: e.toString()));
    }
  }

  Future<void> setBoothReady(String boothId) async {
    try {
      await _setBoothReady(boothId);
    } catch (e) {
      _emit(_state.copyWith(errorMessage: e.toString()));
    }
  }

  @override
  void dispose() {
    _antrianSub?.cancel();
    _boothSub?.cancel();
    super.dispose();
  }
}
