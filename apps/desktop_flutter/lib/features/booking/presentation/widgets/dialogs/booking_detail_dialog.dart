import 'dart:typed_data';

import 'package:desktop_flutter/app/theme/app_colors.dart';
import 'package:desktop_flutter/app/theme/app_text_styles.dart';
import 'package:desktop_flutter/features/booking/application/booking_controller.dart';
import 'package:desktop_flutter/features/booking/domain/entities/booking.dart';
import 'package:desktop_flutter/shared/models/booking_item.dart';
import 'package:desktop_flutter/shared/widgets/base_dialog.dart';
import 'package:desktop_flutter/shared/widgets/dialog_action_button.dart';
import 'package:desktop_flutter/features/booking/presentation/widgets/dialogs/confirm_payment_dialog.dart';
import 'package:flutter/material.dart';

class BookingDetailDialog extends StatefulWidget {
  final BookingController controller;

  const BookingDetailDialog({super.key, required this.controller});

  @override
  State<BookingDetailDialog> createState() => _BookingDetailDialogState();
}

class _BookingDetailDialogState extends State<BookingDetailDialog> {
  BookingItem? _detail;
  bool _loadingDetail = true;
  bool _isProcessing = false;
  String? _error;
  Uint8List? _proofImageBytes;
  bool _loadingProof = false;
  bool _proofError = false;

  Booking get _booking => widget.controller.selectedBooking;

  @override
  void initState() {
    super.initState();
    _fetchDetail();
  }

  Future<void> _fetchDetail() async {
    final recordId = _booking.recordId;
    if (recordId == null) {
      if (!mounted) return;
      setState(() {
        _loadingDetail = false;
        _error = 'ID booking tidak valid.';
      });
      return;
    }

    try {
      final detail = await widget.controller.fetchBookingDetail(recordId);
      if (!mounted) return;
      setState(() {
        _detail = detail;
        _loadingDetail = false;
      });
      if (detail != null && detail.transferProofUrl.isNotEmpty) {
        _loadProofImage(recordId);
      }
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _loadingDetail = false;
        _error = 'Detail booking gagal dimuat.';
      });
    }
  }

  Future<void> _loadProofImage(int bookingId) async {
    _loadingProof = true;
    setState(() {});

    final bytes = await widget.controller.downloadProofImage(bookingId);

    if (!mounted) return;
    setState(() {
      _proofImageBytes = bytes;
      _loadingProof = false;
      _proofError = bytes == null;
    });
  }

  Future<void> _handleVerify() async {
    if (_isProcessing) return;

    if (_booking.canConfirmPayment) {
      final result = await showDialog<ConfirmPaymentDialogResult>(
        context: context,
        builder: (ctx) => ConfirmPaymentDialog(booking: _booking),
      );

      if (result == null) return;

      setState(() => _isProcessing = true);
      await widget.controller.accBooking(
        paymentMethod: result.method,
        paymentAmount: result.amount,
        referenceNo: result.referenceNo,
        notes: result.notes,
      );
    } else {
      setState(() => _isProcessing = true);
      await widget.controller.accBooking();
    }

    if (!mounted) return;

    if (widget.controller.errorMessage == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: const Text(
            'Booking terverifikasi dan otomatis masuk antrean hari ini.',
          ),
          backgroundColor: AppColors.success,
        ),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(widget.controller.errorMessage!),
          backgroundColor: AppColors.error,
        ),
      );
    }

    if (mounted) Navigator.pop(context);
  }

  Future<void> _handleDecline() async {
    if (_isProcessing) return;
    setState(() => _isProcessing = true);

    await widget.controller.cancelBooking();
    if (!mounted) return;
    Navigator.pop(context);
  }

  String _formatPrice(double price) {
    final int p = price.toInt();
    return 'Rp ${p.toString().replaceAllMapped(RegExp(r'\B(?=(\d{3})+(?!\d))'), (m) => '.')}';
  }

  String _statusLabel(String status) {
    return switch (status.toLowerCase()) {
      'pending' => 'MENUNGGU',
      'confirmed' => 'TERKONFIRMASI',
      'paid' => 'LUNAS',
      'checked_in' => 'HADIR',
      'in_queue' => 'DALAM ANTREAN',
      'in_session' => 'SESI BERJALAN',
      'done' => 'SELESAI',
      'cancelled' => 'BATAL',
      _ => status.toUpperCase(),
    };
  }

  @override
  Widget build(BuildContext context) {
    final canVerify = _booking.canConfirmBooking || _booking.canConfirmPayment;
    final canDecline = _booking.canDeclineBooking;

    return BaseDialog(
      width: 560,
      maxHeight: 640,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Detail Booking - ${_booking.id}',
                style: AppTextStyles.h2.copyWith(fontSize: 16),
              ),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 6,
                ),
                decoration: BoxDecoration(
                  color: AppColors.primaryLight,
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  _statusLabel(_booking.status),
                  style: AppTextStyles.caption.copyWith(
                    color: AppColors.primary,
                    fontWeight: FontWeight.w800,
                    fontSize: 10,
                    letterSpacing: 0.5,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),

          // Scrollable content
          Flexible(
            child: SingleChildScrollView(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Customer Info Card
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: AppColors.cardBg,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: AppColors.cardBorder),
                    ),
                    child: Column(
                      children: [
                        _infoRow('Customer', _booking.customerName),
                        const SizedBox(height: 8),
                        _infoRow('Phone', _booking.phone),
                        const SizedBox(height: 8),
                        _infoRow('Waktu', _booking.time),
                        const SizedBox(height: 8),
                        _infoRow('Total', _formatPrice(_booking.totalAmount)),
                        const SizedBox(height: 8),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              'Tipe Bayar',
                              style: AppTextStyles.bodySmall.copyWith(fontSize: 12),
                            ),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                              decoration: BoxDecoration(
                                color: _booking.paymentType == 'dp50'
                                    ? const Color(0xFFFFF7ED)
                                    : const Color(0xFFF0FDF4),
                                borderRadius: BorderRadius.circular(6),
                                border: Border.all(
                                  color: _booking.paymentType == 'dp50'
                                      ? const Color(0xFFF97316).withValues(alpha: 0.3)
                                      : const Color(0xFF22C55E).withValues(alpha: 0.3),
                                ),
                              ),
                              child: Text(
                                _booking.paymentType == 'dp50' ? 'DP 50%' : 'Full Lunas',
                                style: TextStyle(
                                  fontFamily: 'Poppins',
                                  fontSize: 11,
                                  fontWeight: FontWeight.w700,
                                  color: _booking.paymentType == 'dp50'
                                      ? const Color(0xFFF97316)
                                      : const Color(0xFF16A34A),
                                ),
                              ),
                            ),
                          ],
                        ),
                        if (_booking.paymentType == 'dp50') ...[
                          const SizedBox(height: 8),
                          _infoRow('Deposit (DP)', _formatPrice(_booking.depositAmount > 0 ? _booking.depositAmount : _booking.totalAmount * 0.5)),
                        ],
                        if (_detail != null) ...[
                          const SizedBox(height: 8),
                          _infoRow('Sudah Dibayar', _formatPrice(_detail!.paidAmount)),
                          const SizedBox(height: 8),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(
                                'Sisa Bayar',
                                style: AppTextStyles.bodySmall.copyWith(fontSize: 12),
                              ),
                              Text(
                                _formatPrice(_detail!.remainingAmount),
                                style: TextStyle(
                                  fontFamily: 'Poppins',
                                  fontSize: 12,
                                  fontWeight: FontWeight.w700,
                                  color: _detail!.remainingAmount > 0
                                      ? const Color(0xFFEF4444)
                                      : AppColors.success,
                                ),
                              ),
                            ],
                          ),
                        ],
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),

                  // Photo Sharing Consent Checkbox
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                    decoration: BoxDecoration(
                      color: AppColors.cardBg,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: AppColors.cardBorder),
                    ),
                    child: Row(
                      children: [
                        Checkbox(
                          value: widget.controller.allowSharePhotos,
                          onChanged: (val) {
                            setState(() {
                              widget.controller.toggleAllowSharePhotos(val);
                            });
                          },
                          activeColor: AppColors.primary,
                        ),
                        Text(
                          'Izin Share Foto',
                          style: AppTextStyles.bodyMedium,
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),

                  // Payment Proof
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: AppColors.cardBg,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: AppColors.cardBorder),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Bukti Pembayaran',
                          style: AppTextStyles.h4.copyWith(fontSize: 12),
                        ),
                        const SizedBox(height: 8),
                        _buildPaymentProof(),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),

                  // Add-ons
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: AppColors.cardBg,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: AppColors.cardBorder),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Add-ons',
                          style: AppTextStyles.h4.copyWith(fontSize: 12),
                        ),
                        const SizedBox(height: 8),
                        _buildAddOns(),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 20),

          // Action buttons
          Row(
            mainAxisAlignment: MainAxisAlignment.end,
            children: [
              if (canDecline)
                Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: DialogActionButton(
                    label: 'Batal',
                    color: AppColors.warning,
                    onPressed: _isProcessing ? null : _handleDecline,
                    loading: _isProcessing,
                  ),
                ),
              if (canVerify)
                Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: DialogActionButton(
                    label: 'Verifikasi',
                    primary: true,
                    color: AppColors.success,
                    onPressed: _isProcessing ? null : _handleVerify,
                    loading: _isProcessing,
                  ),
                ),
              DialogActionButton(
                label: 'Tutup',
                onPressed: () => Navigator.pop(context),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildPaymentProof() {
    if (_loadingDetail || _loadingProof) {
      return const SizedBox(
        height: 48,
        child: Center(child: CircularProgressIndicator(strokeWidth: 2)),
      );
    }

    if (_error != null) {
      return Text(
        _error!,
        style: TextStyle(fontSize: 12, color: AppColors.error),
      );
    }

    final proofUrl = _detail?.transferProofUrl ?? '';

    if (proofUrl.isEmpty) {
      return const Text(
        'Belum ada bukti pembayaran.',
        style: TextStyle(fontSize: 12, color: AppColors.textMuted),
      );
    }

    if (_proofError) {
      return Container(
        height: 80,
        decoration: BoxDecoration(
          color: AppColors.error.withValues(alpha: 0.08),
          borderRadius: BorderRadius.circular(8),
        ),
        child: const Center(
          child: Text(
            'Gagal memuat bukti pembayaran.',
            style: TextStyle(fontSize: 11, color: AppColors.error),
          ),
        ),
      );
    }

    if (_proofImageBytes != null) {
      return ClipRRect(
        borderRadius: BorderRadius.circular(8),
        child: Image.memory(
          _proofImageBytes!,
          height: 160,
          width: double.infinity,
          fit: BoxFit.contain,
        ),
      );
    }

    return const SizedBox.shrink();
  }

  Widget _buildAddOns() {
    if (_loadingDetail) {
      return const SizedBox(
        height: 32,
        child: Center(child: CircularProgressIndicator(strokeWidth: 2)),
      );
    }

    if (_error != null) {
      return Text(
        _error!,
        style: TextStyle(fontSize: 12, color: AppColors.error),
      );
    }

    final items = _detail?.addOns ?? [];

    if (items.isEmpty) {
      return const Text(
        'Tidak ada add-ons.',
        style: TextStyle(fontSize: 12, color: AppColors.textMuted),
      );
    }

    return Column(
      children: [
        Row(
          children: [
            Expanded(
              flex: 3,
              child: Text(
                'Item',
                style: AppTextStyles.caption.copyWith(
                  fontWeight: FontWeight.w700,
                  color: AppColors.textMuted,
                ),
              ),
            ),
            Expanded(
              flex: 1,
              child: Text(
                'Qty',
                textAlign: TextAlign.right,
                style: AppTextStyles.caption.copyWith(
                  fontWeight: FontWeight.w700,
                  color: AppColors.textMuted,
                ),
              ),
            ),
            Expanded(
              flex: 2,
              child: Text(
                'Total',
                textAlign: TextAlign.right,
                style: AppTextStyles.caption.copyWith(
                  fontWeight: FontWeight.w700,
                  color: AppColors.textMuted,
                ),
              ),
            ),
          ],
        ),
        const Divider(height: 12),
        ...items.map((item) => Padding(
              padding: const EdgeInsets.only(bottom: 6),
              child: Row(
                children: [
                  Expanded(
                    flex: 3,
                    child: Text(
                      item.label,
                      style: const TextStyle(fontSize: 12),
                    ),
                  ),
                  Expanded(
                    flex: 1,
                    child: Text(
                      '${item.qty}',
                      textAlign: TextAlign.right,
                      style: const TextStyle(fontSize: 12),
                    ),
                  ),
                  Expanded(
                    flex: 2,
                    child: Text(
                      _formatPrice(item.lineTotal),
                      textAlign: TextAlign.right,
                      style: const TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                ],
              ),
            )),
      ],
    );
  }

  Widget _infoRow(String label, String value) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: AppTextStyles.bodySmall.copyWith(fontSize: 12),
        ),
        Text(
          value,
          style: const TextStyle(
            fontFamily: 'Poppins',
            fontSize: 12,
            fontWeight: FontWeight.w600,
            color: AppColors.textPrimary,
          ),
        ),
      ],
    );
  }
}
