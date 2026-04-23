// presentation/widgets/booking_queue_list.dart

import 'package:flutter/material.dart';
import '../../domain/order_state.dart';

class BookingQueueList extends StatelessWidget {
  final List<BookingQueue> queues;
  final String searchQuery;

  const BookingQueueList({
    super.key,
    required this.queues,
    this.searchQuery = '',
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Search field
        Container(
          height: 40,
          decoration: BoxDecoration(
            color: Colors.white,
            border: Border.all(color: const Color(0xFFE5E7EB)),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Row(
            children: [
              const SizedBox(width: 10),
              const Icon(Icons.search, size: 16, color: Color(0xFF9CA3AF)),
              const SizedBox(width: 6),
              const Expanded(
                child: Text(
                  'Cari Kode Booking/ WhatsApp',
                  style: TextStyle(fontSize: 12, color: Color(0xFF9CA3AF)),
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 12),
        // Queue items
        ...queues.map((q) => _QueueCard(queue: q)),
        // Pagination
        const SizedBox(height: 16),
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            GestureDetector(
              child: Row(
                children: const [
                  Icon(Icons.chevron_left, size: 16, color: Color(0xFF374151)),
                  Text(
                    'Prev',
                    style: TextStyle(fontSize: 13, color: Color(0xFF374151)),
                  ),
                ],
              ),
            ),
            Row(
              children: [
                Container(
                  width: 8,
                  height: 8,
                  decoration: const BoxDecoration(
                    color: Color(0xFF1E3A5F),
                    shape: BoxShape.circle,
                  ),
                ),
                const SizedBox(width: 4),
                Container(
                  width: 8,
                  height: 8,
                  decoration: const BoxDecoration(
                    color: Color(0xFFD1D5DB),
                    shape: BoxShape.circle,
                  ),
                ),
                const SizedBox(width: 4),
                Container(
                  width: 8,
                  height: 8,
                  decoration: const BoxDecoration(
                    color: Color(0xFFD1D5DB),
                    shape: BoxShape.circle,
                  ),
                ),
              ],
            ),
            GestureDetector(
              child: Row(
                children: const [
                  Text(
                    'Next',
                    style: TextStyle(fontSize: 13, color: Color(0xFF374151)),
                  ),
                  Icon(Icons.chevron_right, size: 16, color: Color(0xFF374151)),
                ],
              ),
            ),
          ],
        ),
      ],
    );
  }
}

class _QueueCard extends StatelessWidget {
  final BookingQueue queue;

  const _QueueCard({required this.queue});

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: queue.isActive ? const Color(0xFF1E3A5F) : Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(
          color: queue.isActive
              ? const Color(0xFF1E3A5F)
              : const Color(0xFFE5E7EB),
        ),
        boxShadow: queue.isActive
            ? []
            : [
                BoxShadow(
                  color: Colors.black.withOpacity(0.04),
                  blurRadius: 4,
                  offset: const Offset(0, 1),
                ),
              ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                queue.code,
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                  color: queue.isActive
                      ? Colors.white.withOpacity(0.8)
                      : const Color(0xFF6B7280),
                ),
              ),
              Text(
                queue.time,
                style: TextStyle(
                  fontSize: 11,
                  color: queue.isActive
                      ? Colors.white.withOpacity(0.6)
                      : const Color(0xFF9CA3AF),
                ),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            queue.customerName,
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w700,
              color: queue.isActive ? Colors.white : const Color(0xFF111827),
            ),
          ),
          const SizedBox(height: 2),
          Text(
            queue.phone,
            style: TextStyle(
              fontSize: 12,
              color: queue.isActive
                  ? Colors.white.withOpacity(0.6)
                  : const Color(0xFF9CA3AF),
            ),
          ),
          const SizedBox(height: 8),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
            decoration: BoxDecoration(
              color: queue.isActive
                  ? Colors.white.withOpacity(0.15)
                  : const Color(0xFFF3F4F6),
              borderRadius: BorderRadius.circular(4),
            ),
            child: Text(
              queue.status,
              style: TextStyle(
                fontSize: 10,
                fontWeight: FontWeight.w700,
                color: queue.isActive ? Colors.white : const Color(0xFF6B7280),
                letterSpacing: 0.5,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
