export async function velocityRequest(url, options = {}) {
    const headers = new Headers(options.headers);
    headers.set('Accept', 'application/json');
    headers.set('X-Requested-With', 'XMLHttpRequest');

    const response = await fetch(url, {
        ...options,
        headers,
        credentials: options.credentials ?? 'same-origin',
    });

    if (! response.ok) {
        const error = new Error(`Velocity request failed with status ${response.status}.`);
        error.response = response;
        throw error;
    }

    return response;
}
