import 'package:desktop_flutter/core/session/api_session.dart';
import 'package:desktop_flutter/features/kasir/services/receipt_printer.dart';
import 'package:flutter/material.dart';
import '../../../../app/theme/app_colors.dart';
import '../../../../app/theme/app_text_styles.dart';
import '../../application/booking_controller.dart';
import '../widgets/package/package_card.dart';
import '../widgets/addon/addon_item.dart';
import '../widgets/summary/order_summary.dart';
import '../widgets/dialogs/checkout_success_dialog.dart';

class WalkinPage extends StatefulWidget {
  const WalkinPage({super.key});

  @override
  State<WalkinPage> createState() => _WalkinPageState();
}

class _WalkinPageState extends State<WalkinPage> {
  late BookingController _controller;

  @override
  void initState() {
    super.initState();
    _controller = BookingController();
    _controller.addListener(() => setState(() {}));
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      body: Row(
        children: [
          // ── LEFT: Main Content (Scrollable & Centered Box) ──────────
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.symmetric(horizontal: 40, vertical: 24),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Walk-in Baru', style: AppTextStyles.h2),
                  if (_controller.errorMessage != null) ...[
                    const SizedBox(height: 12),
                    Text(
                      _controller.errorMessage!,
                      style: const TextStyle(color: Colors.redAccent),
                    ),
                  ],
                  const SizedBox(height: 24),
                  
                  // Customer info row (Stretched)
                  _CustomerInfoRow(controller: _controller),
                  const SizedBox(height: 32),

                  // Pilih Paket
                  Text('Pilih Paket', style: AppTextStyles.h3),
                  const SizedBox(height: 16),
                  if (_controller.isLoading)
                    const Center(child: CircularProgressIndicator())
                  else
                    _PackageSection(controller: _controller),
                  const SizedBox(height: 32),

                  // Add-ons
                  Text('Experience Add-ons', style: AppTextStyles.h3),
                  const SizedBox(height: 16),
                  _AddonSection(controller: _controller),
                ],
              ),
            ),
          ),

          // ── RIGHT: Order Summary (Fixed) ────────────────────────────
          Container(
            width: 320,
            padding: const EdgeInsets.all(16),
            child: OrderSummaryPanel(
              controller: _controller,
              onConfirm: () => _checkoutAndPrint(context),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _checkoutAndPrint(BuildContext context) async {
    final result = await _controller.checkoutWalkIn();

    if (!context.mounted || result == null) {
      return;
    }

    final session = ApiSession.current;
    
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) {
        return CheckoutSuccessDialog(
          result: result,
          selectedPackage: _controller.selectedPackage,
          onPrint: () async {
            await ReceiptPrinter.printTransactionReceipt(
              transaction: result.transaction,
              brandName: 'Ready To Pict',
              branchName: _controller.selectedBranchName,
              cashierName: session?.user.name ?? '-',
              queueCode: result.queueTicket.queueCode,
              paperWidthMm: 80,
            );
          },
          onDone: () {
            Navigator.of(context).pop();
            setState(() {
              _controller = BookingController();
              _controller.addListener(() => setState(() {}));
            });
          },
        );
      },
    );
  }
}

class _CustomerInfoRow extends StatelessWidget {
  final BookingController controller;

  const _CustomerInfoRow({required this.controller});

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Row(
          children: [
            Expanded(
              child: _InfoField(
                label: 'NAMA PELANGGAN',
                value: controller.customerName,
                onChanged: controller.updateCustomerName,
              ),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: _InfoField(
                label: 'WHATSAPP (WAJIB)',
                value: controller.whatsapp,
                keyboardType: TextInputType.phone,
                onChanged: controller.updateWhatsapp,
              ),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: _InfoField(
                label: 'GMAIL',
                value: controller.email,
                keyboardType: TextInputType.emailAddress,
                onChanged: controller.updateEmail,
              ),
            ),
          ],
        ),
        const SizedBox(height: 16),
        Row(
          children: [
            SizedBox(
              width: 120,
              child: _InfoField(
                label: 'JUMLAH ORANG',
                value: controller.jumlahOrang.toString(),
                keyboardType: TextInputType.number,
                onChanged: controller.updateJumlahOrang,
              ),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: _InfoField(
                label: 'NOTE',
                value: controller.note,
                onChanged: controller.updateNote,
              ),
            ),
          ],
        ),
      ],
    );
  }
}

class _InfoField extends StatelessWidget {
  final String label;
  final String value;
  final TextInputType? keyboardType;
  final ValueChanged<String>? onChanged;

  const _InfoField({
    required this.label,
    required this.value,
    this.keyboardType,
    this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: AppTextStyles.caption.copyWith(
            fontWeight: FontWeight.w600,
            letterSpacing: 0.5,
          ),
        ),
        const SizedBox(height: 4),
        Container(
          width: double.infinity,
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
          decoration: BoxDecoration(
            color: AppColors.surface,
            borderRadius: BorderRadius.circular(10),
            border: Border.all(color: AppColors.cardBorder),
          ),
          child: TextFormField(
            initialValue: value,
            readOnly: onChanged == null,
            keyboardType: keyboardType,
            onChanged: onChanged,
            style: AppTextStyles.bodyMedium,
            decoration: const InputDecoration(
              border: InputBorder.none,
              isDense: true,
              contentPadding: EdgeInsets.zero,
            ),
          ),
        ),
      ],
    );
  }
}

class _PackageSection extends StatelessWidget {
  final BookingController controller;

  const _PackageSection({required this.controller});

  @override
  Widget build(BuildContext context) {
    if (controller.packages.isEmpty) {
      return const Text('Belum ada paket aktif.');
    }

    return Wrap(
      spacing: 16,
      runSpacing: 16,
      children: List.generate(controller.packages.length, (index) {
        return SizedBox(
          width: 220,
          child: PackageCard(
            package: controller.packages[index],
            isSelected: controller.selectedPackageIndex == index,
            onTap: () => controller.selectPackage(index),
          ),
        );
      }),
    );
  }
}

class _AddonSection extends StatelessWidget {
  final BookingController controller;

  const _AddonSection({required this.controller});

  @override
  Widget build(BuildContext context) {
    if (controller.addons.isEmpty) {
      return const Text('Tidak ada add-on untuk paket ini.');
    }

    return Column(
      children: List.generate(
        controller.addons.length,
        (index) => Padding(
          padding: const EdgeInsets.only(bottom: 10),
          child: AddonItem(
            addon: controller.addons[index],
            index: index,
            onIncrement: () => controller.incrementAddon(index),
            onDecrement: () => controller.decrementAddon(index),
          ),
        ),
      ),
    );
  }
}
