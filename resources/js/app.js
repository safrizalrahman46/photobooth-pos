import './bootstrap';
import { createApp } from 'vue';
import AdminDashboardApp from './admin/AdminDashboardApp.vue';
import BookingApp from './booking/BookingApp.vue';
import BookingCustomerApp from './booking/BookingCustomerApp.vue';
import PaymentApp from './booking/PaymentApp.vue';
import BookingSuccessApp from './booking/BookingSuccessApp.vue';

const parseProps = (elementId) => {
	const propsNode = document.getElementById(elementId);
	let props = {};

	if (propsNode?.textContent) {
		try {
			props = JSON.parse(propsNode.textContent);
		} catch {
			props = {};
		}
	}

	return props;
};

const mountBookingApp = () => {
	const mountNode = document.getElementById('booking-app');

	if (!mountNode) {
		return;
	}

	const props = parseProps('booking-app-props');

	createApp(BookingApp, props).mount(mountNode);
};

const mountBookingCustomerApp = () => {
	const mountNode = document.getElementById('booking-customer-app');

	if (!mountNode) {
		return;
	}

	const props = parseProps('booking-customer-app-props');

	createApp(BookingCustomerApp, props).mount(mountNode);
};

const mountPaymentApp = () => {
	const mountNode = document.getElementById('booking-payment-app');

	if (!mountNode) {
		return;
	}

	const props = parseProps('booking-payment-app-props');

	createApp(PaymentApp, props).mount(mountNode);
};

const mountBookingSuccessApp = () => {
	const mountNode = document.getElementById('booking-success-app');

	if (!mountNode) {
		return;
	}

	const props = parseProps('booking-success-app-props');

	createApp(BookingSuccessApp, props).mount(mountNode);
};

const mountAdminDashboardApp = () => {
	const mountNode = document.getElementById('admin-dashboard-app');

	if (!mountNode) {
		return;
	}

	const props = parseProps('admin-dashboard-app-props');

	createApp(AdminDashboardApp, props).mount(mountNode);
};

const mountApps = () => {
	mountBookingCustomerApp();
	mountBookingApp();
	mountPaymentApp();
	mountBookingSuccessApp();
	mountAdminDashboardApp();
};

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', mountApps, { once: true });
} else {
	mountApps();
}
