/**
 * EventReservation — WebAuthn / Passkey helpers
 * Compatible with the Symfony API endpoints at /api/auth/passkey/...
 */

function bufferToBase64Url(buffer) {
  const bytes = Array.from(new Uint8Array(buffer));
  const binary = bytes.map(b => String.fromCharCode(b)).join('');
  return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

function base64UrlToBuffer(base64url) {
  let base64 = base64url.replace(/-/g, '+').replace(/_/g, '/');
  const padding = '='.repeat((4 - base64.length % 4) % 4);
  base64 += padding;
  const binary = atob(base64);
  return Uint8Array.from(binary, c => c.charCodeAt(0)).buffer;
}

async function registerPasskey(username) {
  const optRes = await fetch('/api/auth/passkey/register/options', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ username })
  });
  if (!optRes.ok) { const e = await optRes.json(); throw new Error(e.error || 'Options error'); }
  const options = await optRes.json();

  const credential = await navigator.credentials.create({
    publicKey: {
      ...options,
      challenge: base64UrlToBuffer(options.challenge),
      user: { ...options.user, id: base64UrlToBuffer(options.user.id) },
      excludeCredentials: (options.excludeCredentials || []).map(c => ({ ...c, id: base64UrlToBuffer(c.id) }))
    }
  });

  const verRes = await fetch('/api/auth/passkey/register/verify', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      credential: {
        id: credential.id,
        rawId: bufferToBase64Url(credential.rawId),
        response: {
          clientDataJSON: bufferToBase64Url(credential.response.clientDataJSON),
          attestationObject: bufferToBase64Url(credential.response.attestationObject)
        },
        type: credential.type,
        clientExtensionResults: credential.getClientExtensionResults()
      }
    })
  });
  const result = await verRes.json();
  if (!verRes.ok) throw new Error(result.error || 'Verification error');
  if (result.token) {
    localStorage.setItem('jwt_token', result.token);
  }
  return result;
}

async function loginWithPasskey() {
  const optRes = await fetch('/api/auth/passkey/login/options', { method: 'POST' });
  if (!optRes.ok) { const e = await optRes.json(); throw new Error(e.error || 'Options error'); }
  const options = await optRes.json();

  const assertion = await navigator.credentials.get({
    publicKey: {
      ...options,
      challenge: base64UrlToBuffer(options.challenge),
      allowCredentials: (options.allowCredentials || []).map(c => ({ ...c, id: base64UrlToBuffer(c.id) }))
    }
  });

  const verRes = await fetch('/api/auth/passkey/login/verify', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      credential: {
        id: assertion.id,
        rawId: bufferToBase64Url(assertion.rawId),
        response: {
          clientDataJSON: bufferToBase64Url(assertion.response.clientDataJSON),
          authenticatorData: bufferToBase64Url(assertion.response.authenticatorData),
          signature: bufferToBase64Url(assertion.response.signature),
          userHandle: assertion.response.userHandle ? bufferToBase64Url(assertion.response.userHandle) : null
        },
        type: assertion.type,
        clientExtensionResults: assertion.getClientExtensionResults()
      }
    })
  });
  const result = await verRes.json();
  if (!verRes.ok) throw new Error(result.error || 'Login error');
  if (result.token) localStorage.setItem('jwt_token', result.token);
  return result;
}

function authFetch(url, options = {}) {
  const token = localStorage.getItem('jwt_token');
  const headers = { ...(options.headers || {}), 'Authorization': token ? `Bearer ${token}` : '' };
  return fetch(url, { ...options, headers });
}
