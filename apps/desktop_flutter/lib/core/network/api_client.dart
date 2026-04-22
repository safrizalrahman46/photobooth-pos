import 'dart:convert';

import 'package:desktop_flutter/core/config/app_config.dart';
import 'package:desktop_flutter/shared/models/app_settings_payload.dart';
import 'package:desktop_flutter/shared/models/booking_item.dart';
import 'package:desktop_flutter/shared/models/branch_management_item.dart';
import 'package:desktop_flutter/shared/models/branch_option.dart';
import 'package:desktop_flutter/shared/models/package_management_item.dart';
import 'package:desktop_flutter/shared/models/payment_record.dart';
import 'package:desktop_flutter/shared/models/queue_ticket_item.dart';
import 'package:desktop_flutter/shared/models/report_summary.dart';
import 'package:desktop_flutter/shared/models/auth_user.dart';
import 'package:desktop_flutter/shared/models/desktop_session.dart';
import 'package:desktop_flutter/shared/models/transaction_record.dart';
import 'package:desktop_flutter/shared/models/time_slot_management_item.dart';
import 'package:http/http.dart' as http;

class ApiClient {
  ApiClient({required this.baseUrl, this.token});

  final String baseUrl;
  final String? token;

  Future<DesktopSession> login({
    required String email,
    required String password,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/auth/login'),
      headers: _headers(),
      body: jsonEncode({
        'email': email,
        'password': password,
        'device_name': AppConfig.deviceName,
      }),
    );

    final payload = _decode(response.body);

    if (response.statusCode >= 400 || payload['success'] != true) {
      throw ApiException(payload['message']?.toString() ?? 'Login gagal.');
    }

    final data =
        payload['data'] as Map<String, dynamic>? ?? <String, dynamic>{};
    final user = AuthUser.fromJson(data['user'] as Map<String, dynamic>? ?? {});

    return DesktopSession(
      baseUrl: baseUrl,
      token: data['token']?.toString() ?? '',
      user: user,
    );
  }

  Future<void> logout() async {
    if (token == null || token!.isEmpty) {
      return;
    }

    await http.post(
      Uri.parse('$baseUrl/auth/logout'),
      headers: _headers(authenticated: true),
    );
  }

  Future<List<BranchOption>> fetchBranches({
    bool includeInactive = false,
  }) async {
    final useAuth = token != null && token!.isNotEmpty;

    final payload = await _send(
      method: 'GET',
      path: '/branches',
      authenticated: useAuth,
      query: {if (includeInactive) 'include_inactive': '1'},
    );

    final data = payload['data'];

    if (data is! List) {
      return <BranchOption>[];
    }

    return data
        .whereType<Map<String, dynamic>>()
        .map(BranchOption.fromJson)
        .toList();
  }

  Future<List<BranchManagementItem>> fetchManageBranches({
    bool includeInactive = true,
    String? search,
  }) async {
    final payload = await _send(
      method: 'GET',
      path: '/manage/branches',
      authenticated: true,
      query: {
        if (includeInactive) 'include_inactive': '1',
        if (search != null && search.isNotEmpty) 'search': search,
      },
    );

    final data = payload['data'];

    if (data is! List) {
      return <BranchManagementItem>[];
    }

    return data
        .whereType<Map<String, dynamic>>()
        .map(BranchManagementItem.fromJson)
        .toList();
  }

  Future<BranchManagementItem> createBranch({
    required String code,
    required String name,
    required String timezone,
    String? phone,
    String? address,
    bool isActive = true,
  }) async {
    final payload = await _send(
      method: 'POST',
      path: '/manage/branches',
      authenticated: true,
      body: {
        'code': code,
        'name': name,
        'timezone': timezone,
        if (phone != null && phone.isNotEmpty) 'phone': phone,
        if (address != null && address.isNotEmpty) 'address': address,
        'is_active': isActive,
      },
    );

    final data = payload['data'];

    if (data is! Map<String, dynamic>) {
      throw ApiException('Respons pembuatan cabang tidak valid.');
    }

    return BranchManagementItem.fromJson(data);
  }

  Future<BranchManagementItem> updateBranch({
    required int branchId,
    required String code,
    required String name,
    required String timezone,
    String? phone,
    String? address,
    bool isActive = true,
  }) async {
    final payload = await _send(
      method: 'PUT',
      path: '/manage/branches/$branchId',
      authenticated: true,
      body: {
        'code': code,
        'name': name,
        'timezone': timezone,
        if (phone != null && phone.isNotEmpty) 'phone': phone,
        if (address != null && address.isNotEmpty) 'address': address,
        'is_active': isActive,
      },
    );

    final data = payload['data'];

    if (data is! Map<String, dynamic>) {
      throw ApiException('Respons update cabang tidak valid.');
    }

    return BranchManagementItem.fromJson(data);
  }

  Future<List<PackageManagementItem>> fetchManagePackages({
    bool includeInactive = true,
    int? branchId,
    String? search,
    int perPage = 200,
  }) async {
    final payload = await _send(
      method: 'GET',
      path: '/manage/packages',
      authenticated: true,
      query: {
        'per_page': '$perPage',
        if (includeInactive) 'include_inactive': '1',
        if (branchId != null) 'branch_id': '$branchId',
        if (search != null && search.isNotEmpty) 'search': search,
      },
    );

    final data = payload['data'];

    if (data is! List) {
      return <PackageManagementItem>[];
    }

    return data
        .whereType<Map<String, dynamic>>()
        .map(PackageManagementItem.fromJson)
        .toList();
  }

  Future<PackageManagementItem> createPackage({
    int? branchId,
    required String code,
    required String name,
    required int durationMinutes,
    required double basePrice,
    String? description,
    int sortOrder = 0,
    bool isActive = true,
  }) async {
    final payload = await _send(
      method: 'POST',
      path: '/manage/packages',
      authenticated: true,
      body: {
        'branch_id': branchId,
        'code': code,
        'name': name,
        'description': description,
        'duration_minutes': durationMinutes,
        'base_price': basePrice,
        'sort_order': sortOrder,
        'is_active': isActive,
      },
    );

    final data = payload['data'];

    if (data is! Map<String, dynamic>) {
      throw ApiException('Respons pembuatan paket tidak valid.');
    }

    return PackageManagementItem.fromJson(data);
  }

  Future<PackageManagementItem> updatePackage({
    required int packageId,
    int? branchId,
    required String code,
    required String name,
    required int durationMinutes,
    required double basePrice,
    String? description,
    int sortOrder = 0,
    bool isActive = true,
  }) async {
    final payload = await _send(
      method: 'PUT',
      path: '/manage/packages/$packageId',
      authenticated: true,
      body: {
        'branch_id': branchId,
        'code': code,
        'name': name,
        'description': description,
        'duration_minutes': durationMinutes,
        'base_price': basePrice,
        'sort_order': sortOrder,
        'is_active': isActive,
      },
    );

    final data = payload['data'];

    if (data is! Map<String, dynamic>) {
      throw ApiException('Respons update paket tidak valid.');
    }

    return PackageManagementItem.fromJson(data);
  }

  Future<List<TimeSlotManagementItem>> fetchManageTimeSlots({
    int? branchId,
    String? slotDate,
    bool? isBookable,
    int perPage = 200,
  }) async {
    final payload = await _send(
      method: 'GET',
      path: '/manage/time-slots',
      authenticated: true,
      query: {
        'per_page': '$perPage',
        if (branchId != null) 'branch_id': '$branchId',
        if (slotDate != null && slotDate.isNotEmpty) 'slot_date': slotDate,
        if (isBookable != null) 'is_bookable': isBookable ? '1' : '0',
      },
    );

    final data = payload['data'];

    if (data is! List) {
      return <TimeSlotManagementItem>[];
    }

    return data
        .whereType<Map<String, dynamic>>()
        .map(TimeSlotManagementItem.fromJson)
        .toList();
  }

  Future<TimeSlotManagementItem> createTimeSlot({
    required int branchId,
    required String slotDate,
    required String startTime,
    required String endTime,
    required int capacity,
    bool isBookable = true,
  }) async {
    final payload = await _send(
      method: 'POST',
      path: '/manage/time-slots',
      authenticated: true,
      body: {
        'branch_id': branchId,
        'slot_date': slotDate,
        'start_time': startTime,
        'end_time': endTime,
        'capacity': capacity,
        'is_bookable': isBookable,
      },
    );

    final data = payload['data'];

    if (data is! Map<String, dynamic>) {
      throw ApiException('Respons pembuatan slot waktu tidak valid.');
    }

    return TimeSlotManagementItem.fromJson(data);
  }

  Future<TimeSlotManagementItem> updateTimeSlot({
    required int slotId,
    required int branchId,
    required String slotDate,
    required String startTime,
    required String endTime,
    required int capacity,
    bool isBookable = true,
  }) async {
    final payload = await _send(
      method: 'PUT',
      path: '/manage/time-slots/$slotId',
      authenticated: true,
      body: {
        'branch_id': branchId,
        'slot_date': slotDate,
        'start_time': startTime,
        'end_time': endTime,
        'capacity': capacity,
        'is_bookable': isBookable,
      },
    );

    final data = payload['data'];

    if (data is! Map<String, dynamic>) {
      throw ApiException('Respons update slot waktu tidak valid.');
    }

    return TimeSlotManagementItem.fromJson(data);
  }

  Future<void> deleteTimeSlot({required int slotId}) async {
    await _send(
      method: 'DELETE',
      path: '/manage/time-slots/$slotId',
      authenticated: true,
    );
  }

  Future<Map<String, dynamic>> bulkSetTimeSlotsBookable({
    required List<int> slotIds,
    required bool isBookable,
  }) async {
    final payload = await _send(
      method: 'POST',
      path: '/manage/time-slots/bulk-bookable',
      authenticated: true,
      body: {'slot_ids': slotIds, 'is_bookable': isBookable},
    );

    final data = payload['data'];

    if (data is! Map<String, dynamic>) {
      throw ApiException('Respons bulk update slot tidak valid.');
    }

    return data;
  }

  Future<Map<String, dynamic>> generateTimeSlots({
    required int branchId,
    required String startDate,
    required String endDate,
    required String dayStartTime,
    required String dayEndTime,
    required int intervalMinutes,
    required int capacity,
    bool isBookable = true,
  }) async {
    final payload = await _send(
      method: 'POST',
      path: '/manage/time-slots/generate',
      authenticated: true,
      body: {
        'branch_id': branchId,
        'start_date': startDate,
        'end_date': endDate,
        'day_start_time': dayStartTime,
        'day_end_time': dayEndTime,
        'interval_minutes': intervalMinutes,
        'capacity': capacity,
        'is_bookable': isBookable,
      },
    );

    final data = payload['data'];

    if (data is! Map<String, dynamic>) {
      throw ApiException('Respons generate slot tidak valid.');
    }

    return data;
  }

  Future<List<QueueTicketItem>> fetchQueueTickets({
    int? branchId,
    String? queueDate,
    String? status,
    int perPage = 40,
  }) async {
    final query = <String, String>{
      'per_page': '$perPage',
      if (branchId != null) 'branch_id': '$branchId',
      if (queueDate != null && queueDate.isNotEmpty) 'queue_date': queueDate,
      if (status != null && status.isNotEmpty) 'status': status,
    };

    final payload = await _send(
      method: 'GET',
      path: '/queue-tickets',
      authenticated: true,
      query: query,
    );

    final data = payload['data'];

    if (data is! List) {
      return <QueueTicketItem>[];
    }

    return data
        .whereType<Map<String, dynamic>>()
        .map(QueueTicketItem.fromJson)
        .toList();
  }

  Future<List<BookingItem>> fetchBookings({
    int? branchId,
    String? date,
    String? status,
    String? bookingCode,
    int perPage = 40,
  }) async {
    final payload = await _send(
      method: 'GET',
      path: '/bookings',
      authenticated: true,
      query: {
        'per_page': '$perPage',
        if (branchId != null) 'branch_id': '$branchId',
        if (date != null && date.isNotEmpty) 'date': date,
        if (status != null && status.isNotEmpty) 'status': status,
        if (bookingCode != null && bookingCode.isNotEmpty)
          'booking_code': bookingCode,
      },
    );

    final data = payload['data'];

    if (data is! List) {
      return <BookingItem>[];
    }

    return data
        .whereType<Map<String, dynamic>>()
        .map(BookingItem.fromJson)
        .toList();
  }

  Future<QueueTicketItem?> callNext({
    required int branchId,
    required String queueDate,
  }) async {
    final payload = await _send(
      method: 'POST',
      path: '/queue-tickets/call-next',
      authenticated: true,
      body: {'branch_id': branchId, 'queue_date': queueDate},
    );

    final data = payload['data'];

    if (data is! Map<String, dynamic>) {
      return null;
    }

    return QueueTicketItem.fromJson(data);
  }

  Future<void> transitionQueueTicket({
    required int ticketId,
    required String status,
  }) async {
    await _send(
      method: 'PATCH',
      path: '/queue-tickets/$ticketId/status',
      authenticated: true,
      body: {'status': status},
    );
  }

  Future<QueueTicketItem> checkInBooking({required int bookingId}) async {
    final payload = await _send(
      method: 'POST',
      path: '/queue-tickets/check-in',
      authenticated: true,
      body: {'booking_id': bookingId},
    );

    final data = payload['data'];

    if (data is! Map<String, dynamic>) {
      throw ApiException('Respons check-in booking tidak valid.');
    }

    return QueueTicketItem.fromJson(data);
  }

  Future<QueueTicketItem> createWalkInTicket({
    required int branchId,
    required String queueDate,
    required String customerName,
    String? customerPhone,
  }) async {
    final payload = await _send(
      method: 'POST',
      path: '/queue-tickets/walk-in',
      authenticated: true,
      body: {
        'branch_id': branchId,
        'queue_date': queueDate,
        'customer_name': customerName,
        if (customerPhone != null && customerPhone.isNotEmpty)
          'customer_phone': customerPhone,
      },
    );

    final data = payload['data'];

    if (data is! Map<String, dynamic>) {
      throw ApiException('Respons walk-in queue tidak valid.');
    }

    return QueueTicketItem.fromJson(data);
  }

  Future<ReportSummary> fetchReportSummary({
    required DateTime from,
    required DateTime to,
    int? branchId,
  }) async {
    final payload = await _send(
      method: 'GET',
      path: '/reports/summary',
      authenticated: true,
      query: {
        'from': _toDateString(from),
        'to': _toDateString(to),
        if (branchId != null) 'branch_id': '$branchId',
      },
    );

    final data = payload['data'];

    if (data is! Map<String, dynamic>) {
      throw ApiException('Data laporan tidak valid.');
    }

    return ReportSummary.fromJson(data);
  }

  Future<List<TransactionRecord>> fetchTransactions({
    int? branchId,
    String? status,
    int perPage = 20,
  }) async {
    final payload = await _send(
      method: 'GET',
      path: '/transactions',
      authenticated: true,
      query: {
        'per_page': '$perPage',
        if (branchId != null) 'branch_id': '$branchId',
        if (status != null && status.isNotEmpty) 'status': status,
      },
    );

    final data = payload['data'];

    if (data is! List) {
      return <TransactionRecord>[];
    }

    return data
        .whereType<Map<String, dynamic>>()
        .map(TransactionRecord.fromJson)
        .toList();
  }

  Future<TransactionRecord> createTransaction({
    required int branchId,
    required String itemName,
    required double qty,
    required double unitPrice,
    double discountAmount = 0,
    double taxAmount = 0,
    String? notes,
  }) async {
    final payload = await _send(
      method: 'POST',
      path: '/transactions',
      authenticated: true,
      body: {
        'branch_id': branchId,
        'discount_amount': discountAmount,
        'tax_amount': taxAmount,
        'notes': notes,
        'items': [
          {
            'item_type': 'manual',
            'item_name': itemName,
            'qty': qty,
            'unit_price': unitPrice,
          },
        ],
      },
    );

    final data = payload['data'];

    if (data is! Map<String, dynamic>) {
      throw ApiException('Respons transaksi tidak valid.');
    }

    return TransactionRecord.fromJson(data);
  }

  Future<PaymentRecord?> addTransactionPayment({
    required int transactionId,
    required String method,
    required double amount,
    String? referenceNo,
    String? notes,
  }) async {
    final payload = await _send(
      method: 'POST',
      path: '/transactions/$transactionId/payments',
      authenticated: true,
      body: {
        'method': method,
        'amount': amount,
        if (referenceNo != null && referenceNo.isNotEmpty)
          'reference_no': referenceNo,
        if (notes != null && notes.isNotEmpty) 'notes': notes,
      },
    );

    final data = payload['data'];

    if (data is! Map<String, dynamic>) {
      return null;
    }

    final payment = data['payment'];

    if (payment is! Map<String, dynamic>) {
      return null;
    }

    return PaymentRecord.fromJson(payment);
  }

  Future<AppSettingsPayload> fetchAppSettings() async {
    final payload = await _send(
      method: 'GET',
      path: '/app-settings',
      authenticated: true,
    );

    final data = payload['data'];

    if (data is! Map<String, dynamic>) {
      throw ApiException('Data pengaturan tidak valid.');
    }

    return AppSettingsPayload.fromJson(data);
  }

  Future<Map<String, dynamic>> updateAppSettingGroup({
    required String group,
    required Map<String, dynamic> value,
  }) async {
    final payload = await _send(
      method: 'PUT',
      path: '/app-settings/$group',
      authenticated: true,
      body: {'value': value},
    );

    final data = payload['data'];

    if (data is! Map<String, dynamic>) {
      throw ApiException('Respons pengaturan tidak valid.');
    }

    return data;
  }

  String _toDateString(DateTime value) {
    final year = value.year.toString().padLeft(4, '0');
    final month = value.month.toString().padLeft(2, '0');
    final day = value.day.toString().padLeft(2, '0');

    return '$year-$month-$day';
  }

  Future<Map<String, dynamic>> _send({
    required String method,
    required String path,
    required bool authenticated,
    Map<String, String>? query,
    Map<String, dynamic>? body,
  }) async {
    final uri = Uri.parse('$baseUrl$path').replace(queryParameters: query);

    final response = switch (method.toUpperCase()) {
      'GET' => await http.get(
        uri,
        headers: _headers(authenticated: authenticated),
      ),
      'POST' => await http.post(
        uri,
        headers: _headers(authenticated: authenticated),
        body: body != null ? jsonEncode(body) : null,
      ),
      'PATCH' => await http.patch(
        uri,
        headers: _headers(authenticated: authenticated),
        body: body != null ? jsonEncode(body) : null,
      ),
      'PUT' => await http.put(
        uri,
        headers: _headers(authenticated: authenticated),
        body: body != null ? jsonEncode(body) : null,
      ),
      'DELETE' => await http.delete(
        uri,
        headers: _headers(authenticated: authenticated),
        body: body != null ? jsonEncode(body) : null,
      ),
      _ => throw ApiException('Metode HTTP tidak didukung: $method'),
    };

    final payload = _decode(response.body);

    if (response.statusCode >= 400 || payload['success'] != true) {
      throw ApiException(payload['message']?.toString() ?? 'Permintaan gagal.');
    }

    return payload;
  }

  Map<String, String> _headers({bool authenticated = false}) {
    return <String, String>{
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      if (authenticated && token != null && token!.isNotEmpty)
        'Authorization': 'Bearer $token',
    };
  }

  Map<String, dynamic> _decode(String body) {
    if (body.isEmpty) {
      return <String, dynamic>{};
    }

    final decoded = jsonDecode(body);

    if (decoded is Map<String, dynamic>) {
      return decoded;
    }

    return <String, dynamic>{};
  }
}

class ApiException implements Exception {
  ApiException(this.message);

  final String message;

  @override
  String toString() => message;
}
