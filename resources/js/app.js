import './bootstrap';

import Alpine from 'alpinejs';
import notifications from './notifications';

window.Alpine = Alpine;

Alpine.data('notifications', notifications);

Alpine.start();
