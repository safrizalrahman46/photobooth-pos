import './bootstrap';
import { createApp } from 'vue';
import BookingApp from './booking/BookingApp.vue';
import PaymentApp from './booking/PaymentApp.vue';

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

const mountPaymentApp = () => {
	const mountNode = document.getElementById('booking-payment-app');

	if (!mountNode) {
		return;
	}

	const props = parseProps('booking-payment-app-props');

	createApp(PaymentApp, props).mount(mountNode);
};

const mountApps = () => {
	mountBookingApp();
	mountPaymentApp();
};

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', mountApps, { once: true });
} else {
	mountApps();
}
