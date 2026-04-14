import './bootstrap';
import { createApp } from 'vue';
import BookingApp from './booking/BookingApp.vue';
import PaymentApp from './booking/PaymentApp.vue';
import BookingCustomerApp from './booking/BookingCustomerApp.vue';
import BookingSuccessApp from './booking/BookingSuccessApp.vue';
import AdminDashboardApp from './admin/AdminDashboardApp.vue';

const mountedApps = new Map();

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
		const activeApp = mountedApps.get('booking-app');

		if (activeApp) {
			activeApp.unmount();
			mountedApps.delete('booking-app');
		}

		return;
	}

	const activeApp = mountedApps.get('booking-app');

	if (activeApp) {
		activeApp.unmount();
		mountNode.innerHTML = '';
	}

	const props = parseProps('booking-app-props');
	const app = createApp(BookingApp, props);

	app.mount(mountNode);
	mountedApps.set('booking-app', app);
};

const mountBookingCustomerApp = () => {
	const mountNode = document.getElementById('booking-customer-app');

	if (!mountNode) {
		const activeApp = mountedApps.get('booking-customer-app');

		if (activeApp) {
			activeApp.unmount();
			mountedApps.delete('booking-customer-app');
		}

		return;
	}

	const activeApp = mountedApps.get('booking-customer-app');

	if (activeApp) {
		activeApp.unmount();
		mountNode.innerHTML = '';
	}

	const props = parseProps('booking-customer-app-props');
	const app = createApp(BookingCustomerApp, props);

	app.mount(mountNode);
	mountedApps.set('booking-customer-app', app);
};

const mountPaymentApp = () => {
	const mountNode = document.getElementById('booking-payment-app');

	if (!mountNode) {
		const activeApp = mountedApps.get('booking-payment-app');

		if (activeApp) {
			activeApp.unmount();
			mountedApps.delete('booking-payment-app');
		}

		return;
	}

	const activeApp = mountedApps.get('booking-payment-app');

	if (activeApp) {
		activeApp.unmount();
		mountNode.innerHTML = '';
	}

	const props = parseProps('booking-payment-app-props');
	const app = createApp(PaymentApp, props);

	app.mount(mountNode);
	mountedApps.set('booking-payment-app', app);
};

const mountBookingSuccessApp = () => {
	const mountNode = document.getElementById('booking-success-app');

	if (!mountNode) {
		const activeApp = mountedApps.get('booking-success-app');

		if (activeApp) {
			activeApp.unmount();
			mountedApps.delete('booking-success-app');
		}

		return;
	}

	const activeApp = mountedApps.get('booking-success-app');

	if (activeApp) {
		activeApp.unmount();
		mountNode.innerHTML = '';
	}

	const props = parseProps('booking-success-app-props');
	const app = createApp(BookingSuccessApp, props);

	app.mount(mountNode);
	mountedApps.set('booking-success-app', app);
};

const mountAdminDashboardApp = () => {
	const mountNode = document.getElementById('admin-dashboard-app');

	if (!mountNode) {
		const activeApp = mountedApps.get('admin-dashboard-app');

		if (activeApp) {
			activeApp.unmount();
			mountedApps.delete('admin-dashboard-app');
		}

		return;
	}

	const activeApp = mountedApps.get('admin-dashboard-app');

	if (activeApp) {
		activeApp.unmount();
		mountNode.innerHTML = '';
	}

	const props = parseProps('admin-dashboard-app-props');
	const app = createApp(AdminDashboardApp, props);

	app.mount(mountNode);
	mountedApps.set('admin-dashboard-app', app);
};

const mountApps = () => {
	mountBookingApp();
	mountBookingCustomerApp();
	mountPaymentApp();
	mountBookingSuccessApp();
	mountAdminDashboardApp();
};

const scheduleMountApps = () => {
	window.requestAnimationFrame(() => {
		mountApps();
	});
};

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', scheduleMountApps, { once: true });
} else {
	scheduleMountApps();
}

document.addEventListener('livewire:navigated', scheduleMountApps);
