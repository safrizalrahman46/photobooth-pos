// presentation/pages/antrian_page.dart

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../application/bloc/antrian_bloc.dart';
import '../theme/app_theme.dart';
import '../widgets/antrian_berikutnya_banner.dart';
import '../widgets/kolom_menunggu_widget.dart';
import '../widgets/kolom_sedang_foto_widget.dart';
import '../widgets/kolom_selesai_widget.dart';
import '../../domain/entities/booth_entity.dart';

class AntrianPage extends StatelessWidget {
  const AntrianPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Consumer<AntrianBloc>(
      builder: (context, bloc, _) {
        final state = bloc.state;

        if (state.isLoading) {
          return const Center(
            child: CircularProgressIndicator(color: AppTheme.primary),
          );
        }

        if (state.errorMessage != null) {
          return Center(
            child: Text(
              'Error: ${state.errorMessage}',
              style: const TextStyle(color: Colors.red),
            ),
          );
        }

        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Banner Antrian Berikutnya
            AntrianBerikutnyaBanner(antrian: state.antrianBerikutnya),
            const SizedBox(height: 24),

            // 3 Kolom utama
            Expanded(
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // ─── Kolom Menunggu ─────────────────────
                  Expanded(
                    child: KolomMenungguWidget(antrian: state.antrianMenunggu),
                  ),
                  const SizedBox(width: 16),

                  // ─── Kolom Sedang Foto ──────────────────
                  Expanded(
                    child: KolomSedangFotoWidget(
                      booths: state.booths,
                      onKirimKeSelesai: () {
                        final boothAktif = state.booths.firstWhere(
                          (b) => b.antrianAktif != null,
                          orElse: () => state.booths.first,
                        );
                        if (boothAktif.antrianAktif != null) {
                          bloc.pindahKeSelesai(
                            boothAktif.antrianAktif!.nomorAntrian,
                            'Cetak Berhasil',
                          );
                        }
                      },
                      onSetReady: () {
                        final boothTersedia = state.booths.firstWhere(
                          (b) => b.status == StatusBooth.tersedia,
                          orElse: () => state.booths.last,
                        );
                        bloc.setBoothReady(boothTersedia.id);
                      },
                    ),
                  ),
                  const SizedBox(width: 16),

                  // ─── Kolom Selesai ──────────────────────
                  Expanded(
                    child: KolomSelesaiWidget(
                      antrian: state.antrianSelesai,
                      onLihatSemua: () {
                        // Navigate to history page
                      },
                    ),
                  ),
                ],
              ),
            ),
          ],
        );
      },
    );
  }
}
