import http from 'k6/http';
import { check, sleep } from 'k6';

export let options = {
    stages: [
        { duration: '1m', target: 20 },
        { duration: '3m', target: 100 },
        { duration: '2m', target: 0 },
    ],
    thresholds: {
        http_req_duration: ['p(95)<800'],
        http_req_failed: ['rate<0.02'],
    },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

export default function () {
    let payload = JSON.stringify({
        name: `Test Tenant ${Math.random().toString(36).substring(7)}`,
        email: `test${Math.random().toString(36).substring(7)}@example.com`,
        password: 'password123',
        password_confirmation: 'password123',
    });

    let response = http.post(`${BASE_URL}/api/register-tenant`, payload, {
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
    });

    check(response, {
        'status is 201 or 422': (r) => r.status === 201 || r.status === 422,
        'response time < 800ms': (r) => r.timings.duration < 800,
    });

    sleep(1);
}