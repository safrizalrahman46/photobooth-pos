import 'package:desktop_flutter/core/network/api_client.dart';
import 'package:desktop_flutter/shared/models/app_settings_payload.dart';
import 'package:desktop_flutter/shared/models/branch_management_item.dart';
import 'package:desktop_flutter/shared/models/branch_option.dart';
import 'package:desktop_flutter/shared/models/desktop_session.dart';
import 'package:desktop_flutter/shared/models/package_management_item.dart';
import 'package:desktop_flutter/shared/models/report_summary.dart';
import 'package:desktop_flutter/shared/models/time_slot_management_item.dart';
import 'package:flutter/material.dart';

class OwnerDashboardPanel extends StatefulWidget {
  const OwnerDashboardPanel(this.session, {super.key});

  final DesktopSession session;

  @override
  State<OwnerDashboardPanel> createState() => _OwnerDashboardPanelState();
}

class _OwnerDashboardPanelState extends State<OwnerDashboardPanel> {
  late final ApiClient _client = ApiClient(
    baseUrl: widget.session.baseUrl,
    token: widget.session.token,
  );

  final TextEditingController _brandNameController = TextEditingController();
  final TextEditingController _shortNameController = TextEditingController();
  final TextEditingController _supportEmailController = TextEditingController();
  final TextEditingController _supportPhoneController = TextEditingController();
  final TextEditingController _holdMinutesController = TextEditingController();
  final TextEditingController _arrivalNoticeController =
      TextEditingController();
  final TextEditingController _branchCodeController = TextEditingController();
  final TextEditingController _branchNameController = TextEditingController();
  final TextEditingController _branchTimezoneController = TextEditingController(
    text: 'Asia/Jakarta',
  );
  final TextEditingController _branchPhoneController = TextEditingController();
  final TextEditingController _branchAddressController =
      TextEditingController();
  final TextEditingController _packageCodeController = TextEditingController();
  final TextEditingController _packageNameController = TextEditingController();
  final TextEditingController _packageDescriptionController =
      TextEditingController();
  final TextEditingController _packageDurationController =
      TextEditingController(text: '30');
  final TextEditingController _packagePriceController = TextEditingController(
    text: '0',
  );
  final TextEditingController _packageSortOrderController =
      TextEditingController(text: '0');
  final TextEditingController _managementSearchController =
      TextEditingController();
  final TextEditingController _slotDateController = TextEditingController();
  final TextEditingController _slotStartController = TextEditingController(
    text: '09:00',
  );
  final TextEditingController _slotEndController = TextEditingController(
    text: '09:30',
  );
  final TextEditingController _slotCapacityController = TextEditingController(
    text: '1',
  );
  final TextEditingController _slotFilterDateController =
      TextEditingController();
  final TextEditingController _slotGenStartDateController =
      TextEditingController();
  final TextEditingController _slotGenEndDateController =
      TextEditingController();
  final TextEditingController _slotGenStartTimeController =
      TextEditingController(text: '09:00');
  final TextEditingController _slotGenEndTimeController = TextEditingController(
    text: '21:00',
  );
  final TextEditingController _slotGenIntervalController =
      TextEditingController(text: '30');
  final TextEditingController _slotGenCapacityController =
      TextEditingController(text: '1');

  List<BranchOption> _branches = <BranchOption>[];
  List<BranchManagementItem> _managedBranches = <BranchManagementItem>[];
  List<PackageManagementItem> _managedPackages = <PackageManagementItem>[];
  List<TimeSlotManagementItem> _managedSlots = <TimeSlotManagementItem>[];
  final Set<int> _selectedSlotIds = <int>{};
  int? _branchId;
  int? _editBranchId;
  int? _editPackageId;
  int? _editSlotId;
  int? _packageBranchId;
  int? _slotBranchId;
  int? _slotFilterBranchId;
  String _range = '7';
  int _tabIndex = 0;
  ReportSummary? _summary;
  bool _queueBoardEnabled = true;
  bool _onsiteEnabled = true;
  bool _midtransEnabled = false;
  bool _branchActive = true;
  bool _packageActive = true;
  bool _slotBookable = true;
  bool _loading = true;
  bool _savingSettings = false;
  bool _savingBranch = false;
  bool _savingPackage = false;
  bool _savingSlot = false;
  bool _generatingSlots = false;
  bool _bulkUpdatingSlots = false;
  bool _deletingSlots = false;
  String? _error;
  String? _success;

  @override
  void initState() {
    super.initState();
    _slotDateController.text = _todayDate;
    _slotFilterDateController.text = _todayDate;
    _slotGenStartDateController.text = _todayDate;
    _slotGenEndDateController.text = _todayDate;
    _loadData();
  }

  @override
  void dispose() {
    _brandNameController.dispose();
    _shortNameController.dispose();
    _supportEmailController.dispose();
    _supportPhoneController.dispose();
    _holdMinutesController.dispose();
    _arrivalNoticeController.dispose();
    _branchCodeController.dispose();
    _branchNameController.dispose();
    _branchTimezoneController.dispose();
    _branchPhoneController.dispose();
    _branchAddressController.dispose();
    _packageCodeController.dispose();
    _packageNameController.dispose();
    _packageDescriptionController.dispose();
    _packageDurationController.dispose();
    _packagePriceController.dispose();
    _packageSortOrderController.dispose();
    _managementSearchController.dispose();
    _slotDateController.dispose();
    _slotStartController.dispose();
    _slotEndController.dispose();
    _slotCapacityController.dispose();
    _slotFilterDateController.dispose();
    _slotGenStartDateController.dispose();
    _slotGenEndDateController.dispose();
    _slotGenStartTimeController.dispose();
    _slotGenEndTimeController.dispose();
    _slotGenIntervalController.dispose();
    _slotGenCapacityController.dispose();
    super.dispose();
  }

  Future<void> _loadData() async {
    setState(() {
      _loading = true;
      _error = null;
      _success = null;
    });

    try {
      final branches = await _client.fetchBranches(includeInactive: true);
      final managedBranches = await _client.fetchManageBranches(
        includeInactive: true,
      );
      final managedPackages = await _client.fetchManagePackages(
        includeInactive: true,
        search: _managementSearchController.text.trim().isEmpty
            ? null
            : _managementSearchController.text.trim(),
      );
      final managedSlots = await _client.fetchManageTimeSlots(
        branchId: _slotFilterBranchId,
        slotDate: _slotFilterDateController.text.trim().isEmpty
            ? null
            : _slotFilterDateController.text.trim(),
      );
      final now = DateTime.now();
      final rangeDays = int.tryParse(_range) ?? 7;
      final from = now.subtract(Duration(days: rangeDays - 1));

      final summary = await _client.fetchReportSummary(
        from: from,
        to: now,
        branchId: _branchId,
      );

      final settings = await _client.fetchAppSettings();

      if (!mounted) {
        return;
      }

      setState(() {
        _branches = branches;
        _managedBranches = managedBranches;
        _managedPackages = managedPackages;
        _managedSlots = managedSlots;
        _selectedSlotIds.removeWhere(
          (id) => !managedSlots.any((slot) => slot.id == id),
        );
        if (_slotBranchId == null && managedBranches.isNotEmpty) {
          _slotBranchId = managedBranches.first.id;
        }
        _summary = summary;
      });

      _applySettings(settings);
    } on ApiException catch (exception) {
      if (!mounted) {
        return;
      }
      setState(() {
        _error = exception.message;
      });
    } finally {
      if (mounted) {
        setState(() {
          _loading = false;
        });
      }
    }
  }

  void _applySettings(AppSettingsPayload settings) {
    final general = settings.general;
    final booking = settings.booking;
    final payment = settings.payment;

    _brandNameController.text = general['brand_name']?.toString() ?? '';
    _shortNameController.text = general['short_name']?.toString() ?? '';
    _supportEmailController.text = general['support_email']?.toString() ?? '';
    _supportPhoneController.text = general['support_phone']?.toString() ?? '';
    _holdMinutesController.text = (booking['hold_minutes'] ?? 15).toString();
    _arrivalNoticeController.text = (booking['arrival_notice_minutes'] ?? 10)
        .toString();

    setState(() {
      _queueBoardEnabled = booking['queue_board_enabled'] == true;
      _onsiteEnabled = payment['onsite_enabled'] != false;
      _midtransEnabled = payment['midtrans_enabled'] == true;
    });
  }

  Future<void> _saveSettings() async {
    if (_savingSettings) {
      return;
    }

    final holdMinutes = int.tryParse(_holdMinutesController.text.trim()) ?? 15;
    final arrivalMinutes =
        int.tryParse(_arrivalNoticeController.text.trim()) ?? 10;

    setState(() {
      _savingSettings = true;
      _error = null;
      _success = null;
    });

    try {
      await _client.updateAppSettingGroup(
        group: 'general',
        value: {
          'brand_name': _brandNameController.text.trim(),
          'short_name': _shortNameController.text.trim(),
          'support_email': _supportEmailController.text.trim(),
          'support_phone': _supportPhoneController.text.trim(),
        },
      );

      await _client.updateAppSettingGroup(
        group: 'booking',
        value: {
          'hold_minutes': holdMinutes,
          'arrival_notice_minutes': arrivalMinutes,
          'queue_board_enabled': _queueBoardEnabled,
        },
      );

      await _client.updateAppSettingGroup(
        group: 'payment',
        value: {
          'onsite_enabled': _onsiteEnabled,
          'midtrans_enabled': _midtransEnabled,
          'currency': 'IDR',
        },
      );

      if (!mounted) {
        return;
      }

      setState(() {
        _success = 'Pengaturan website berhasil diperbarui.';
      });
    } on ApiException catch (exception) {
      if (!mounted) {
        return;
      }

      setState(() {
        _error = exception.message;
      });
    } finally {
      if (mounted) {
        setState(() {
          _savingSettings = false;
        });
      }
    }
  }

  Future<void> _reloadManagementData() async {
    try {
      final managedBranches = await _client.fetchManageBranches(
        includeInactive: true,
        search: _managementSearchController.text.trim().isEmpty
            ? null
            : _managementSearchController.text.trim(),
      );
      final managedPackages = await _client.fetchManagePackages(
        includeInactive: true,
        search: _managementSearchController.text.trim().isEmpty
            ? null
            : _managementSearchController.text.trim(),
      );
      final managedSlots = await _client.fetchManageTimeSlots(
        branchId: _slotFilterBranchId,
        slotDate: _slotFilterDateController.text.trim().isEmpty
            ? null
            : _slotFilterDateController.text.trim(),
      );

      if (!mounted) {
        return;
      }

      setState(() {
        _managedBranches = managedBranches;
        _managedPackages = managedPackages;
        _managedSlots = managedSlots;
        if (_slotBranchId == null && managedBranches.isNotEmpty) {
          _slotBranchId = managedBranches.first.id;
        }
      });
    } on ApiException catch (exception) {
      if (!mounted) {
        return;
      }

      setState(() {
        _error = exception.message;
      });
    }
  }

  void _fillBranchForm(BranchManagementItem? branch) {
    if (branch == null) {
      _editBranchId = null;
      _branchCodeController.clear();
      _branchNameController.clear();
      _branchTimezoneController.text = 'Asia/Jakarta';
      _branchPhoneController.clear();
      _branchAddressController.clear();
      _branchActive = true;
      return;
    }

    _editBranchId = branch.id;
    _branchCodeController.text = branch.code;
    _branchNameController.text = branch.name;
    _branchTimezoneController.text = branch.timezone;
    _branchPhoneController.text = branch.phone;
    _branchAddressController.text = branch.address;
    _branchActive = branch.isActive;
  }

  void _fillPackageForm(PackageManagementItem? package) {
    if (package == null) {
      _editPackageId = null;
      _packageBranchId = null;
      _packageCodeController.clear();
      _packageNameController.clear();
      _packageDescriptionController.clear();
      _packageDurationController.text = '30';
      _packagePriceController.text = '0';
      _packageSortOrderController.text = '0';
      _packageActive = true;
      return;
    }

    _editPackageId = package.id;
    _packageBranchId = package.branchId;
    _packageCodeController.text = package.code;
    _packageNameController.text = package.name;
    _packageDescriptionController.text = package.description;
    _packageDurationController.text = package.durationMinutes.toString();
    _packagePriceController.text = package.basePrice.toStringAsFixed(0);
    _packageSortOrderController.text = package.sortOrder.toString();
    _packageActive = package.isActive;
  }

  Future<void> _saveBranch() async {
    if (_savingBranch) {
      return;
    }

    final code = _branchCodeController.text.trim().toUpperCase();
    final name = _branchNameController.text.trim();
    final timezone = _branchTimezoneController.text.trim();

    if (code.isEmpty || name.isEmpty || timezone.isEmpty) {
      setState(() {
        _error = 'Kode, nama, dan timezone cabang wajib diisi.';
      });
      return;
    }

    setState(() {
      _savingBranch = true;
      _error = null;
      _success = null;
    });

    try {
      if (_editBranchId == null) {
        await _client.createBranch(
          code: code,
          name: name,
          timezone: timezone,
          phone: _branchPhoneController.text.trim(),
          address: _branchAddressController.text.trim(),
          isActive: _branchActive,
        );
      } else {
        await _client.updateBranch(
          branchId: _editBranchId!,
          code: code,
          name: name,
          timezone: timezone,
          phone: _branchPhoneController.text.trim(),
          address: _branchAddressController.text.trim(),
          isActive: _branchActive,
        );
      }

      await _loadData();

      if (!mounted) {
        return;
      }

      setState(() {
        _success = _editBranchId == null
            ? 'Cabang baru berhasil dibuat.'
            : 'Cabang berhasil diperbarui.';
      });

      _fillBranchForm(null);
    } on ApiException catch (exception) {
      if (!mounted) {
        return;
      }

      setState(() {
        _error = exception.message;
      });
    } finally {
      if (mounted) {
        setState(() {
          _savingBranch = false;
        });
      }
    }
  }

  Future<void> _savePackage() async {
    if (_savingPackage) {
      return;
    }

    final code = _packageCodeController.text.trim().toUpperCase();
    final name = _packageNameController.text.trim();
    final duration = int.tryParse(_packageDurationController.text.trim()) ?? 0;
    final price =
        double.tryParse(
          _packagePriceController.text.trim().replaceAll(',', '.'),
        ) ??
        -1;
    final sortOrder =
        int.tryParse(_packageSortOrderController.text.trim()) ?? 0;

    if (code.isEmpty || name.isEmpty || duration <= 0 || price < 0) {
      setState(() {
        _error = 'Kode, nama, durasi (>0), dan harga paket (>=0) harus valid.';
      });

      return;
    }

    setState(() {
      _savingPackage = true;
      _error = null;
      _success = null;
    });

    try {
      if (_editPackageId == null) {
        await _client.createPackage(
          branchId: _packageBranchId,
          code: code,
          name: name,
          description: _packageDescriptionController.text.trim(),
          durationMinutes: duration,
          basePrice: price,
          sortOrder: sortOrder,
          isActive: _packageActive,
        );
      } else {
        await _client.updatePackage(
          packageId: _editPackageId!,
          branchId: _packageBranchId,
          code: code,
          name: name,
          description: _packageDescriptionController.text.trim(),
          durationMinutes: duration,
          basePrice: price,
          sortOrder: sortOrder,
          isActive: _packageActive,
        );
      }

      await _reloadManagementData();

      if (!mounted) {
        return;
      }

      setState(() {
        _success = _editPackageId == null
            ? 'Paket baru berhasil dibuat.'
            : 'Paket berhasil diperbarui.';
      });

      _fillPackageForm(null);
    } on ApiException catch (exception) {
      if (!mounted) {
        return;
      }

      setState(() {
        _error = exception.message;
      });
    } finally {
      if (mounted) {
        setState(() {
          _savingPackage = false;
        });
      }
    }
  }

  Future<void> _reloadSlotData() async {
    try {
      final managedSlots = await _client.fetchManageTimeSlots(
        branchId: _slotFilterBranchId,
        slotDate: _slotFilterDateController.text.trim().isEmpty
            ? null
            : _slotFilterDateController.text.trim(),
      );

      if (!mounted) {
        return;
      }

      setState(() {
        _managedSlots = managedSlots;
        _selectedSlotIds.removeWhere(
          (id) => !managedSlots.any((slot) => slot.id == id),
        );
      });
    } on ApiException catch (exception) {
      if (!mounted) {
        return;
      }

      setState(() {
        _error = exception.message;
      });
    }
  }

  void _fillSlotForm(TimeSlotManagementItem? slot) {
    if (slot == null) {
      _editSlotId = null;
      _slotBranchId = _managedBranches.isEmpty
          ? null
          : _managedBranches.first.id;
      _slotDateController.text = _todayDate;
      _slotStartController.text = '09:00';
      _slotEndController.text = '09:30';
      _slotCapacityController.text = '1';
      _slotBookable = true;
      return;
    }

    _editSlotId = slot.id;
    _slotBranchId = slot.branchId;
    _slotDateController.text = slot.slotDate;
    _slotStartController.text = slot.startTime;
    _slotEndController.text = slot.endTime;
    _slotCapacityController.text = slot.capacity.toString();
    _slotBookable = slot.isBookable;
  }

  Future<void> _saveSlot() async {
    if (_savingSlot) {
      return;
    }

    final branchId = _slotBranchId;
    final slotDate = _slotDateController.text.trim();
    final startTime = _slotStartController.text.trim();
    final endTime = _slotEndController.text.trim();
    final capacity = int.tryParse(_slotCapacityController.text.trim()) ?? 0;

    if (branchId == null ||
        slotDate.isEmpty ||
        startTime.isEmpty ||
        endTime.isEmpty ||
        capacity <= 0) {
      setState(() {
        _error =
            'Cabang, tanggal, jam mulai/akhir, dan kapasitas (>0) wajib diisi.';
      });
      return;
    }

    setState(() {
      _savingSlot = true;
      _error = null;
      _success = null;
    });

    try {
      final normalizedStart = _normalizeTimeInput(startTime);
      final normalizedEnd = _normalizeTimeInput(endTime);

      if (_editSlotId == null) {
        await _client.createTimeSlot(
          branchId: branchId,
          slotDate: slotDate,
          startTime: normalizedStart,
          endTime: normalizedEnd,
          capacity: capacity,
          isBookable: _slotBookable,
        );
      } else {
        await _client.updateTimeSlot(
          slotId: _editSlotId!,
          branchId: branchId,
          slotDate: slotDate,
          startTime: normalizedStart,
          endTime: normalizedEnd,
          capacity: capacity,
          isBookable: _slotBookable,
        );
      }

      await _reloadSlotData();

      if (!mounted) {
        return;
      }

      setState(() {
        _success = _editSlotId == null
            ? 'Slot jam baru berhasil dibuat.'
            : 'Slot jam berhasil diperbarui.';
      });

      _fillSlotForm(null);
    } on ApiException catch (exception) {
      if (!mounted) {
        return;
      }

      setState(() {
        _error = exception.message;
      });
    } finally {
      if (mounted) {
        setState(() {
          _savingSlot = false;
        });
      }
    }
  }

  Future<void> _generateSlots() async {
    if (_generatingSlots) {
      return;
    }

    final branchId = _slotBranchId;
    final startDate = _slotGenStartDateController.text.trim();
    final endDate = _slotGenEndDateController.text.trim();
    final dayStartTime = _slotGenStartTimeController.text.trim();
    final dayEndTime = _slotGenEndTimeController.text.trim();
    final interval = int.tryParse(_slotGenIntervalController.text.trim()) ?? 0;
    final capacity = int.tryParse(_slotGenCapacityController.text.trim()) ?? 0;

    if (branchId == null ||
        startDate.isEmpty ||
        endDate.isEmpty ||
        dayStartTime.isEmpty ||
        dayEndTime.isEmpty ||
        interval <= 0 ||
        capacity <= 0) {
      setState(() {
        _error =
            'Generator slot wajib diisi lengkap (cabang, range tanggal, jam operasional, interval, kapasitas).';
      });
      return;
    }

    final confirmed = await _confirmSlotAction(
      title: 'Generate slot jam?',
      message:
          'Slot akan dibuat dari $startDate s/d $endDate, pukul ${_normalizeTimeInput(dayStartTime)} - ${_normalizeTimeInput(dayEndTime)}, interval $interval menit.',
      confirmLabel: 'Generate',
    );

    if (!confirmed) {
      return;
    }

    setState(() {
      _generatingSlots = true;
      _error = null;
      _success = null;
    });

    try {
      final result = await _client.generateTimeSlots(
        branchId: branchId,
        startDate: startDate,
        endDate: endDate,
        dayStartTime: _normalizeTimeInput(dayStartTime),
        dayEndTime: _normalizeTimeInput(dayEndTime),
        intervalMinutes: interval,
        capacity: capacity,
        isBookable: _slotBookable,
      );

      await _reloadSlotData();

      if (!mounted) {
        return;
      }

      final created = (result['created_count'] as num?)?.toInt() ?? 0;
      final skipped = (result['skipped_count'] as num?)?.toInt() ?? 0;

      setState(() {
        _success =
            'Generate selesai: $created slot dibuat, $skipped slot dilewati (overlap).';
      });
    } on ApiException catch (exception) {
      if (!mounted) {
        return;
      }

      setState(() {
        _error = exception.message;
      });
    } finally {
      if (mounted) {
        setState(() {
          _generatingSlots = false;
        });
      }
    }
  }

  Future<void> _bulkUpdateSelectedSlotsBookable(bool isBookable) async {
    if (_bulkUpdatingSlots) {
      return;
    }

    if (_selectedSlotIds.isEmpty) {
      setState(() {
        _error = 'Pilih minimal satu slot dulu.';
      });
      return;
    }

    final confirmed = await _confirmSlotAction(
      title: isBookable ? 'Aktifkan slot terpilih?' : 'Blokir slot terpilih?',
      message: isBookable
          ? '${_selectedSlotIds.length} slot akan dijadikan bookable.'
          : '${_selectedSlotIds.length} slot akan diblokir dari booking.',
      confirmLabel: isBookable ? 'Aktifkan' : 'Blokir',
    );

    if (!confirmed) {
      return;
    }

    setState(() {
      _bulkUpdatingSlots = true;
      _error = null;
      _success = null;
    });

    try {
      final result = await _client.bulkSetTimeSlotsBookable(
        slotIds: _selectedSlotIds.toList(),
        isBookable: isBookable,
      );

      await _reloadSlotData();

      if (!mounted) {
        return;
      }

      final updated = (result['updated_count'] as num?)?.toInt() ?? 0;

      setState(() {
        _success = isBookable
            ? '$updated slot berhasil diaktifkan.'
            : '$updated slot berhasil diblokir.';
      });
    } on ApiException catch (exception) {
      if (!mounted) {
        return;
      }

      setState(() {
        _error = exception.message;
      });
    } finally {
      if (mounted) {
        setState(() {
          _bulkUpdatingSlots = false;
        });
      }
    }
  }

  Future<void> _deleteSelectedSlots() async {
    if (_deletingSlots) {
      return;
    }

    final ids = _selectedSlotIds.toList();

    if (ids.isEmpty) {
      setState(() {
        _error = 'Pilih minimal satu slot untuk dihapus.';
      });
      return;
    }

    final confirmed = await _confirmSlotAction(
      title: 'Hapus slot terpilih?',
      message:
          '${ids.length} slot akan dihapus permanen. Aksi ini tidak bisa dibatalkan.',
      confirmLabel: 'Hapus',
      destructive: true,
    );

    if (!confirmed) {
      return;
    }

    setState(() {
      _deletingSlots = true;
      _error = null;
      _success = null;
    });

    var deleted = 0;
    var failed = 0;

    for (final id in ids) {
      try {
        await _client.deleteTimeSlot(slotId: id);
        deleted++;
      } on ApiException {
        failed++;
      }
    }

    await _reloadSlotData();

    if (!mounted) {
      return;
    }

    setState(() {
      _selectedSlotIds.clear();
      if (deleted > 0) {
        _success = failed > 0
            ? '$deleted slot dihapus, $failed gagal dihapus.'
            : '$deleted slot berhasil dihapus.';
      }
      if (failed > 0 && deleted == 0) {
        _error = 'Gagal menghapus slot terpilih.';
      }
      _deletingSlots = false;
    });
  }

  Future<void> _deleteSingleSlot(TimeSlotManagementItem slot) async {
    if (_deletingSlots) {
      return;
    }

    final confirmed = await _confirmSlotAction(
      title: 'Hapus slot ini?',
      message:
          'Slot ${slot.slotDate} ${slot.startTime}-${slot.endTime} akan dihapus permanen.',
      confirmLabel: 'Hapus',
      destructive: true,
    );

    if (!confirmed) {
      return;
    }

    setState(() {
      _deletingSlots = true;
      _error = null;
      _success = null;
    });

    try {
      await _client.deleteTimeSlot(slotId: slot.id);
      await _reloadSlotData();

      if (!mounted) {
        return;
      }

      setState(() {
        _selectedSlotIds.remove(slot.id);
        if (_editSlotId == slot.id) {
          _fillSlotForm(null);
        }
        _success =
            'Slot ${slot.slotDate} ${slot.startTime}-${slot.endTime} dihapus.';
      });
    } on ApiException catch (exception) {
      if (!mounted) {
        return;
      }

      setState(() {
        _error = exception.message;
      });
    } finally {
      if (mounted) {
        setState(() {
          _deletingSlots = false;
        });
      }
    }
  }

  void _toggleSlotSelection(int slotId, bool selected) {
    setState(() {
      if (selected) {
        _selectedSlotIds.add(slotId);
      } else {
        _selectedSlotIds.remove(slotId);
      }
    });
  }

  void _toggleSelectAllSlots(bool selected) {
    setState(() {
      if (selected) {
        _selectedSlotIds
          ..clear()
          ..addAll(_managedSlots.map((slot) => slot.id));
      } else {
        _selectedSlotIds.clear();
      }
    });
  }

  Future<bool> _confirmSlotAction({
    required String title,
    required String message,
    required String confirmLabel,
    bool destructive = false,
  }) async {
    final result = await showDialog<bool>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: Text(title),
          content: Text(message),
          actions: <Widget>[
            TextButton(
              onPressed: () => Navigator.of(context).pop(false),
              child: const Text('Batal'),
            ),
            FilledButton(
              style: destructive
                  ? FilledButton.styleFrom(backgroundColor: Colors.red)
                  : null,
              onPressed: () => Navigator.of(context).pop(true),
              child: Text(confirmLabel),
            ),
          ],
        );
      },
    );

    return result == true;
  }

  String _normalizeTimeInput(String value) {
    final trimmed = value.trim();

    if (RegExp(r'^\d{2}:\d{2}$').hasMatch(trimmed)) {
      return trimmed;
    }

    if (RegExp(r'^\d{2}:\d{2}:\d{2}$').hasMatch(trimmed)) {
      return trimmed.substring(0, 5);
    }

    return trimmed;
  }

  String get _todayDate {
    final now = DateTime.now();
    final year = now.year.toString().padLeft(4, '0');
    final month = now.month.toString().padLeft(2, '0');
    final day = now.day.toString().padLeft(2, '0');

    return '$year-$month-$day';
  }

  String _branchLabel(int? branchId) {
    if (branchId == null) {
      return 'Global';
    }

    final match = _managedBranches.where((branch) => branch.id == branchId);

    if (match.isEmpty) {
      return 'Cabang #$branchId';
    }

    return match.first.name;
  }

  String _rupiah(double value) {
    final number = value.isNaN ? 0 : value;
    final integer = number.round();

    final chars = integer.toString().split('').reversed.toList();
    final buffer = StringBuffer();

    for (var i = 0; i < chars.length; i++) {
      if (i != 0 && i % 3 == 0) {
        buffer.write('.');
      }
      buffer.write(chars[i]);
    }

    return 'Rp ${buffer.toString().split('').reversed.join()}';
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: <Widget>[
        Text(
          'Workspace Owner',
          style: Theme.of(
            context,
          ).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w700),
        ),
        const SizedBox(height: 12),
        Wrap(
          spacing: 12,
          runSpacing: 12,
          crossAxisAlignment: WrapCrossAlignment.center,
          children: <Widget>[
            SegmentedButton<int>(
              segments: const <ButtonSegment<int>>[
                ButtonSegment<int>(
                  value: 0,
                  label: Text('Overview'),
                  icon: Icon(Icons.insights_outlined),
                ),
                ButtonSegment<int>(
                  value: 1,
                  label: Text('Management'),
                  icon: Icon(Icons.tune_outlined),
                ),
                ButtonSegment<int>(
                  value: 2,
                  label: Text('Slot Jam'),
                  icon: Icon(Icons.schedule_outlined),
                ),
              ],
              selected: <int>{_tabIndex},
              onSelectionChanged: (selection) {
                setState(() {
                  _tabIndex = selection.first;
                });
              },
            ),
            if (_tabIndex == 0)
              DropdownButton<String>(
                value: _range,
                items: const <DropdownMenuItem<String>>[
                  DropdownMenuItem(value: '7', child: Text('7 hari')),
                  DropdownMenuItem(value: '14', child: Text('14 hari')),
                  DropdownMenuItem(value: '30', child: Text('30 hari')),
                ],
                onChanged: (value) {
                  if (value == null) {
                    return;
                  }

                  setState(() {
                    _range = value;
                  });

                  _loadData();
                },
              ),
            if (_tabIndex == 0)
              SizedBox(
                width: 260,
                child: DropdownButtonFormField<int?>(
                  key: ValueKey<int?>(_branchId),
                  initialValue: _branchId,
                  decoration: const InputDecoration(labelText: 'Cabang'),
                  items: <DropdownMenuItem<int?>>[
                    const DropdownMenuItem<int?>(
                      value: null,
                      child: Text('Semua cabang'),
                    ),
                    ..._branches.map(
                      (branch) => DropdownMenuItem<int?>(
                        value: branch.id,
                        child: Text(branch.name),
                      ),
                    ),
                  ],
                  onChanged: (value) {
                    setState(() {
                      _branchId = value;
                    });
                    _loadData();
                  },
                ),
              ),
            if (_tabIndex == 1)
              SizedBox(
                width: 320,
                child: TextField(
                  controller: _managementSearchController,
                  decoration: const InputDecoration(
                    labelText: 'Cari code/nama cabang atau paket',
                  ),
                  onSubmitted: (_) => _reloadManagementData(),
                ),
              ),
            if (_tabIndex == 2)
              SizedBox(
                width: 260,
                child: DropdownButtonFormField<int?>(
                  key: ValueKey<int?>(_slotFilterBranchId),
                  initialValue: _slotFilterBranchId,
                  decoration: const InputDecoration(labelText: 'Filter cabang'),
                  items: <DropdownMenuItem<int?>>[
                    const DropdownMenuItem<int?>(
                      value: null,
                      child: Text('Semua cabang'),
                    ),
                    ..._managedBranches.map(
                      (branch) => DropdownMenuItem<int?>(
                        value: branch.id,
                        child: Text(branch.name),
                      ),
                    ),
                  ],
                  onChanged: (value) {
                    setState(() {
                      _slotFilterBranchId = value;
                    });
                    _reloadSlotData();
                  },
                ),
              ),
            if (_tabIndex == 2)
              SizedBox(
                width: 180,
                child: TextField(
                  controller: _slotFilterDateController,
                  decoration: const InputDecoration(
                    labelText: 'Filter tanggal',
                  ),
                  onSubmitted: (_) => _reloadSlotData(),
                ),
              ),
            FilledButton.icon(
              onPressed: _loading
                  ? null
                  : _tabIndex == 0
                  ? _loadData
                  : _tabIndex == 1
                  ? _reloadManagementData
                  : _reloadSlotData,
              icon: const Icon(Icons.refresh_rounded),
              label: const Text('Refresh'),
            ),
          ],
        ),
        if (_error != null) ...<Widget>[
          const SizedBox(height: 10),
          Text(_error!, style: const TextStyle(color: Colors.redAccent)),
        ],
        if (_success != null) ...<Widget>[
          const SizedBox(height: 10),
          Text(_success!, style: const TextStyle(color: Colors.green)),
        ],
        const SizedBox(height: 16),
        Expanded(
          child: _loading
              ? const Center(child: CircularProgressIndicator())
              : _tabIndex == 0
              ? _buildOverviewSection(context)
              : _tabIndex == 1
              ? _buildManagementSection(context)
              : _buildSlotSection(context),
        ),
      ],
    );
  }

  Widget _buildOverviewSection(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: <Widget>[
        Expanded(
          child: _summary == null
              ? const Center(child: Text('Belum ada data laporan.'))
              : GridView.count(
                  crossAxisCount: 2,
                  mainAxisSpacing: 16,
                  crossAxisSpacing: 16,
                  childAspectRatio: 1.9,
                  children: <Widget>[
                    _MetricCard(
                      title: 'Omzet Dibayar',
                      value: _rupiah(_summary!.combinedPaidSales),
                      subtitle: 'POS + booking online',
                      tone: const Color(0xFF0F766E),
                    ),
                    _MetricCard(
                      title: 'Total Booking',
                      value: '${_summary!.totalBookings}',
                      subtitle:
                          'Done ${_summary!.doneBookings} • Batal ${_summary!.cancelledBookings}',
                      tone: const Color(0xFF1D4ED8),
                    ),
                    _MetricCard(
                      title: 'Total Queue',
                      value: '${_summary!.totalQueue}',
                      subtitle:
                          'Finished ${_summary!.finishedQueue} • Cancelled ${_summary!.cancelledQueue}',
                      tone: const Color(0xFFB45309),
                    ),
                    _MetricCard(
                      title: 'Transaksi POS',
                      value: '${_summary!.transactionCount}',
                      subtitle: 'Gross ${_rupiah(_summary!.grossSales)}',
                      tone: const Color(0xFF4F46E5),
                    ),
                  ],
                ),
        ),
        const SizedBox(width: 14),
        SizedBox(
          width: 420,
          child: Card(
            elevation: 0,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(20),
            ),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: SingleChildScrollView(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: <Widget>[
                    Text(
                      'Website Settings',
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 14),
                    TextField(
                      controller: _brandNameController,
                      decoration: const InputDecoration(
                        labelText: 'Brand Name',
                      ),
                    ),
                    const SizedBox(height: 10),
                    TextField(
                      controller: _shortNameController,
                      decoration: const InputDecoration(
                        labelText: 'Short Name',
                      ),
                    ),
                    const SizedBox(height: 10),
                    TextField(
                      controller: _supportEmailController,
                      decoration: const InputDecoration(
                        labelText: 'Support Email',
                      ),
                    ),
                    const SizedBox(height: 10),
                    TextField(
                      controller: _supportPhoneController,
                      decoration: const InputDecoration(
                        labelText: 'Support Phone',
                      ),
                    ),
                    const SizedBox(height: 10),
                    Row(
                      children: <Widget>[
                        Expanded(
                          child: TextField(
                            controller: _holdMinutesController,
                            keyboardType: TextInputType.number,
                            decoration: const InputDecoration(
                              labelText: 'Hold Minutes',
                            ),
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: TextField(
                            controller: _arrivalNoticeController,
                            keyboardType: TextInputType.number,
                            decoration: const InputDecoration(
                              labelText: 'Arrival Notice (min)',
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 10),
                    SwitchListTile(
                      contentPadding: EdgeInsets.zero,
                      title: const Text('Queue Board Enabled'),
                      value: _queueBoardEnabled,
                      onChanged: (value) {
                        setState(() {
                          _queueBoardEnabled = value;
                        });
                      },
                    ),
                    SwitchListTile(
                      contentPadding: EdgeInsets.zero,
                      title: const Text('Onsite Payment Enabled'),
                      value: _onsiteEnabled,
                      onChanged: (value) {
                        setState(() {
                          _onsiteEnabled = value;
                        });
                      },
                    ),
                    SwitchListTile(
                      contentPadding: EdgeInsets.zero,
                      title: const Text('Midtrans Enabled'),
                      value: _midtransEnabled,
                      onChanged: (value) {
                        setState(() {
                          _midtransEnabled = value;
                        });
                      },
                    ),
                    const SizedBox(height: 12),
                    FilledButton.icon(
                      onPressed: _savingSettings ? null : _saveSettings,
                      icon: const Icon(Icons.save_outlined),
                      label: Text(
                        _savingSettings ? 'Menyimpan...' : 'Simpan Settings',
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildManagementSection(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: <Widget>[
        Expanded(child: _buildBranchManagementCard(context)),
        const SizedBox(width: 14),
        Expanded(child: _buildPackageManagementCard(context)),
      ],
    );
  }

  Widget _buildSlotSection(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: <Widget>[
        SizedBox(width: 420, child: _buildSlotFormCard(context)),
        const SizedBox(width: 14),
        Expanded(child: _buildSlotListCard(context)),
      ],
    );
  }

  Widget _buildSlotFormCard(BuildContext context) {
    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: SingleChildScrollView(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: <Widget>[
              Text(
                'Form Slot Jam',
                style: Theme.of(
                  context,
                ).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
              ),
              const SizedBox(height: 12),
              DropdownButtonFormField<int?>(
                key: ValueKey<int?>(_slotBranchId),
                initialValue: _slotBranchId,
                decoration: const InputDecoration(labelText: 'Cabang'),
                items: _managedBranches
                    .map(
                      (branch) => DropdownMenuItem<int?>(
                        value: branch.id,
                        child: Text(branch.name),
                      ),
                    )
                    .toList(),
                onChanged: (value) {
                  setState(() {
                    _slotBranchId = value;
                  });
                },
              ),
              const SizedBox(height: 10),
              TextField(
                controller: _slotDateController,
                decoration: const InputDecoration(
                  labelText: 'Tanggal (YYYY-MM-DD)',
                ),
              ),
              const SizedBox(height: 10),
              Row(
                children: <Widget>[
                  Expanded(
                    child: TextField(
                      controller: _slotStartController,
                      decoration: const InputDecoration(
                        labelText: 'Mulai (HH:MM)',
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: TextField(
                      controller: _slotEndController,
                      decoration: const InputDecoration(
                        labelText: 'Selesai (HH:MM)',
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 10),
              TextField(
                controller: _slotCapacityController,
                keyboardType: TextInputType.number,
                decoration: const InputDecoration(labelText: 'Kapasitas'),
              ),
              SwitchListTile(
                contentPadding: EdgeInsets.zero,
                title: const Text('Bookable'),
                value: _slotBookable,
                onChanged: (value) {
                  setState(() {
                    _slotBookable = value;
                  });
                },
              ),
              Row(
                children: <Widget>[
                  OutlinedButton(
                    onPressed: () {
                      setState(() {
                        _fillSlotForm(null);
                      });
                    },
                    child: const Text('Reset'),
                  ),
                  const SizedBox(width: 8),
                  FilledButton.icon(
                    onPressed: _savingSlot ? null : _saveSlot,
                    icon: const Icon(Icons.save_outlined),
                    label: Text(
                      _savingSlot
                          ? 'Menyimpan...'
                          : _editSlotId == null
                          ? 'Tambah Slot'
                          : 'Update Slot',
                    ),
                  ),
                ],
              ),
              const Divider(height: 26),
              Text(
                'Bulk Generator Slot',
                style: Theme.of(
                  context,
                ).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w700),
              ),
              const SizedBox(height: 8),
              Row(
                children: <Widget>[
                  Expanded(
                    child: TextField(
                      controller: _slotGenStartDateController,
                      decoration: const InputDecoration(
                        labelText: 'Mulai tanggal',
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: TextField(
                      controller: _slotGenEndDateController,
                      decoration: const InputDecoration(
                        labelText: 'Sampai tanggal',
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 10),
              Row(
                children: <Widget>[
                  Expanded(
                    child: TextField(
                      controller: _slotGenStartTimeController,
                      decoration: const InputDecoration(
                        labelText: 'Jam mulai operasional',
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: TextField(
                      controller: _slotGenEndTimeController,
                      decoration: const InputDecoration(
                        labelText: 'Jam selesai operasional',
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 10),
              Row(
                children: <Widget>[
                  Expanded(
                    child: TextField(
                      controller: _slotGenIntervalController,
                      keyboardType: TextInputType.number,
                      decoration: const InputDecoration(
                        labelText: 'Interval (menit)',
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: TextField(
                      controller: _slotGenCapacityController,
                      keyboardType: TextInputType.number,
                      decoration: const InputDecoration(labelText: 'Kapasitas'),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 10),
              FilledButton.icon(
                onPressed: _generatingSlots ? null : _generateSlots,
                icon: const Icon(Icons.auto_fix_high_outlined),
                label: Text(
                  _generatingSlots ? 'Generating...' : 'Generate Slot Range',
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildSlotListCard(BuildContext context) {
    final allSelected =
        _managedSlots.isNotEmpty &&
        _selectedSlotIds.length == _managedSlots.length;

    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            Text(
              'Daftar Slot Jam',
              style: Theme.of(
                context,
              ).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 8),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              crossAxisAlignment: WrapCrossAlignment.center,
              children: <Widget>[
                Row(
                  mainAxisSize: MainAxisSize.min,
                  children: <Widget>[
                    Checkbox(
                      value: allSelected,
                      onChanged: _managedSlots.isEmpty
                          ? null
                          : (value) => _toggleSelectAllSlots(value ?? false),
                    ),
                    const Text('Pilih semua'),
                  ],
                ),
                FilledButton.tonal(
                  onPressed: _bulkUpdatingSlots || _selectedSlotIds.isEmpty
                      ? null
                      : () => _bulkUpdateSelectedSlotsBookable(true),
                  child: const Text('Aktifkan'),
                ),
                FilledButton.tonal(
                  onPressed: _bulkUpdatingSlots || _selectedSlotIds.isEmpty
                      ? null
                      : () => _bulkUpdateSelectedSlotsBookable(false),
                  child: const Text('Blokir'),
                ),
                OutlinedButton.icon(
                  onPressed: _deletingSlots || _selectedSlotIds.isEmpty
                      ? null
                      : _deleteSelectedSlots,
                  icon: const Icon(Icons.delete_outline),
                  label: const Text('Hapus Pilihan'),
                ),
              ],
            ),
            const SizedBox(height: 10),
            Expanded(
              child: _managedSlots.isEmpty
                  ? const Center(
                      child: Text('Belum ada slot untuk filter ini.'),
                    )
                  : ListView.separated(
                      itemBuilder: (context, index) {
                        final slot = _managedSlots[index];
                        final selected = _selectedSlotIds.contains(slot.id);

                        return ListTile(
                          dense: true,
                          contentPadding: EdgeInsets.zero,
                          leading: Checkbox(
                            value: selected,
                            onChanged: (value) =>
                                _toggleSlotSelection(slot.id, value ?? false),
                          ),
                          title: Text(
                            '${slot.branchName} • ${slot.slotDate}',
                            style: const TextStyle(fontWeight: FontWeight.w600),
                          ),
                          subtitle: Text(
                            '${slot.startTime} - ${slot.endTime} • cap ${slot.capacity} • ${slot.isBookable ? 'bookable' : 'blocked'}',
                          ),
                          trailing: Wrap(
                            spacing: 4,
                            children: <Widget>[
                              TextButton(
                                onPressed: () {
                                  setState(() {
                                    _fillSlotForm(slot);
                                  });
                                },
                                child: const Text('Edit'),
                              ),
                              IconButton(
                                tooltip: 'Hapus slot',
                                onPressed: _deletingSlots
                                    ? null
                                    : () => _deleteSingleSlot(slot),
                                icon: const Icon(Icons.delete_outline),
                              ),
                            ],
                          ),
                        );
                      },
                      separatorBuilder: (_, __) => const Divider(),
                      itemCount: _managedSlots.length,
                    ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildBranchManagementCard(BuildContext context) {
    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            Text(
              'Manajemen Cabang',
              style: Theme.of(
                context,
              ).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _branchCodeController,
              textCapitalization: TextCapitalization.characters,
              decoration: const InputDecoration(labelText: 'Kode cabang'),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: _branchNameController,
              decoration: const InputDecoration(labelText: 'Nama cabang'),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: _branchTimezoneController,
              decoration: const InputDecoration(labelText: 'Timezone'),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: _branchPhoneController,
              decoration: const InputDecoration(labelText: 'Telepon'),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: _branchAddressController,
              decoration: const InputDecoration(labelText: 'Alamat'),
              maxLines: 2,
            ),
            SwitchListTile(
              contentPadding: EdgeInsets.zero,
              title: const Text('Aktif'),
              value: _branchActive,
              onChanged: (value) {
                setState(() {
                  _branchActive = value;
                });
              },
            ),
            Row(
              children: <Widget>[
                OutlinedButton(
                  onPressed: () {
                    setState(() {
                      _fillBranchForm(null);
                    });
                  },
                  child: const Text('Reset'),
                ),
                const SizedBox(width: 8),
                FilledButton.icon(
                  onPressed: _savingBranch ? null : _saveBranch,
                  icon: const Icon(Icons.save_outlined),
                  label: Text(
                    _savingBranch
                        ? 'Menyimpan...'
                        : _editBranchId == null
                        ? 'Tambah Cabang'
                        : 'Update Cabang',
                  ),
                ),
              ],
            ),
            const Divider(height: 24),
            Text(
              'Daftar Cabang',
              style: Theme.of(
                context,
              ).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 8),
            Expanded(
              child: _managedBranches.isEmpty
                  ? const Center(child: Text('Belum ada data cabang.'))
                  : ListView.separated(
                      itemBuilder: (context, index) {
                        final branch = _managedBranches[index];

                        return ListTile(
                          dense: true,
                          contentPadding: EdgeInsets.zero,
                          title: Text(
                            '${branch.code} • ${branch.name}',
                            style: const TextStyle(fontWeight: FontWeight.w600),
                          ),
                          subtitle: Text(
                            '${branch.isActive ? 'active' : 'inactive'} • ${branch.timezone}',
                          ),
                          trailing: TextButton(
                            onPressed: () {
                              setState(() {
                                _fillBranchForm(branch);
                              });
                            },
                            child: const Text('Edit'),
                          ),
                        );
                      },
                      separatorBuilder: (_, __) => const Divider(),
                      itemCount: _managedBranches.length,
                    ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPackageManagementCard(BuildContext context) {
    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            Text(
              'Manajemen Paket',
              style: Theme.of(
                context,
              ).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 12),
            DropdownButtonFormField<int?>(
              initialValue: _packageBranchId,
              decoration: const InputDecoration(labelText: 'Cabang paket'),
              items: <DropdownMenuItem<int?>>[
                const DropdownMenuItem<int?>(
                  value: null,
                  child: Text('Global (semua cabang)'),
                ),
                ..._managedBranches.map(
                  (branch) => DropdownMenuItem<int?>(
                    value: branch.id,
                    child: Text(branch.name),
                  ),
                ),
              ],
              onChanged: (value) {
                setState(() {
                  _packageBranchId = value;
                });
              },
            ),
            const SizedBox(height: 10),
            TextField(
              controller: _packageCodeController,
              textCapitalization: TextCapitalization.characters,
              decoration: const InputDecoration(labelText: 'Kode paket'),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: _packageNameController,
              decoration: const InputDecoration(labelText: 'Nama paket'),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: _packageDescriptionController,
              decoration: const InputDecoration(labelText: 'Deskripsi'),
              maxLines: 2,
            ),
            const SizedBox(height: 10),
            Row(
              children: <Widget>[
                Expanded(
                  child: TextField(
                    controller: _packageDurationController,
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(
                      labelText: 'Durasi (menit)',
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: TextField(
                    controller: _packagePriceController,
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(labelText: 'Harga dasar'),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: TextField(
                    controller: _packageSortOrderController,
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(labelText: 'Sort'),
                  ),
                ),
              ],
            ),
            SwitchListTile(
              contentPadding: EdgeInsets.zero,
              title: const Text('Aktif'),
              value: _packageActive,
              onChanged: (value) {
                setState(() {
                  _packageActive = value;
                });
              },
            ),
            Row(
              children: <Widget>[
                OutlinedButton(
                  onPressed: () {
                    setState(() {
                      _fillPackageForm(null);
                    });
                  },
                  child: const Text('Reset'),
                ),
                const SizedBox(width: 8),
                FilledButton.icon(
                  onPressed: _savingPackage ? null : _savePackage,
                  icon: const Icon(Icons.save_outlined),
                  label: Text(
                    _savingPackage
                        ? 'Menyimpan...'
                        : _editPackageId == null
                        ? 'Tambah Paket'
                        : 'Update Paket',
                  ),
                ),
              ],
            ),
            const Divider(height: 24),
            Text(
              'Daftar Paket',
              style: Theme.of(
                context,
              ).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 8),
            Expanded(
              child: _managedPackages.isEmpty
                  ? const Center(child: Text('Belum ada data paket.'))
                  : ListView.separated(
                      itemBuilder: (context, index) {
                        final package = _managedPackages[index];

                        return ListTile(
                          dense: true,
                          contentPadding: EdgeInsets.zero,
                          title: Text(
                            '${package.code} • ${package.name}',
                            style: const TextStyle(fontWeight: FontWeight.w600),
                          ),
                          subtitle: Text(
                            '${package.isActive ? 'active' : 'inactive'} • ${_branchLabel(package.branchId)} • ${_rupiah(package.basePrice)}',
                          ),
                          trailing: TextButton(
                            onPressed: () {
                              setState(() {
                                _fillPackageForm(package);
                              });
                            },
                            child: const Text('Edit'),
                          ),
                        );
                      },
                      separatorBuilder: (_, __) => const Divider(),
                      itemCount: _managedPackages.length,
                    ),
            ),
          ],
        ),
      ),
    );
  }
}

class _MetricCard extends StatelessWidget {
  const _MetricCard({
    required this.title,
    required this.value,
    required this.subtitle,
    required this.tone,
  });

  final String title;
  final String value;
  final String subtitle;
  final Color tone;

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            Text(
              title,
              style: Theme.of(
                context,
              ).textTheme.labelLarge?.copyWith(color: tone),
            ),
            const Spacer(),
            Text(
              value,
              style: Theme.of(
                context,
              ).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 6),
            Text(subtitle, style: Theme.of(context).textTheme.bodySmall),
          ],
        ),
      ),
    );
  }
}
