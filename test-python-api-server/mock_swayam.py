import time
import base64
from flask import Flask, request, redirect, jsonify
import jwt
from cryptography.hazmat.primitives.asymmetric import rsa
from cryptography.hazmat.primitives import serialization
from cryptography.hazmat.backends import default_backend

app = Flask(__name__)
session_store = {}

# 1. Generate an RSA Key Pair in memory (required for OIDC JWT signing)
private_key = rsa.generate_private_key(public_exponent=65537, key_size=2048, backend=default_backend())
public_key = private_key.public_key()

private_pem = private_key.private_bytes(
    encoding=serialization.Encoding.PEM,
    format=serialization.PrivateFormat.TraditionalOpenSSL,
    encryption_algorithm=serialization.NoEncryption()
)
public_numbers = public_key.public_numbers()

def int_to_base64url(n):
    hex_str = '%x' % n
    if len(hex_str) % 2 != 0: hex_str = '0' + hex_str
    return base64.urlsafe_b64encode(bytes.fromhex(hex_str)).decode('utf-8').rstrip('=')

# ==========================================
# OPENID CONNECT (SSO) ENDPOINTS
# ==========================================

@app.route('/oidc/.well-known/openid-configuration', methods=['GET'])
def discovery():
    base = request.host_url.rstrip('/')
    return jsonify({
        "issuer": f"{base}/oidc",
        "authorization_endpoint": f"{base}/oidc/auth",
        "token_endpoint": f"{base}/oidc/token",
        "userinfo_endpoint": f"{base}/oidc/me",
        "jwks_uri": f"{base}/oidc/jwks",
        "response_types_supported": ["code"],
        "id_token_signing_alg_values_supported": ["RS256"],
        "claims_supported": ["sub", "email", "name", "given_name", "family_name"]
    })

@app.route('/oidc/jwks', methods=['GET'])
def jwks():
    return jsonify({
        "keys": [{
            "kty": "RSA", "alg": "RS256", "use": "sig", "kid": "mock-key-1",
            "n": int_to_base64url(public_numbers.n),
            "e": int_to_base64url(public_numbers.e)
        }]
    })

@app.route('/oidc/auth', methods=['GET'])
def auth():
    # Moodle redirects the user here to log in. We automatically approve it and bounce them back.
    redirect_uri = request.args.get('redirect_uri')
    state = request.args.get('state')
    nonce = request.args.get('nonce', '')
    
    code = f"mock_code_{int(time.time())}"
    session_store[code] = {"nonce": nonce} # Cache nonce to inject into the JWT later
    
    return redirect(f"{redirect_uri}?code={code}&state={state}")

@app.route('/oidc/token', methods=['POST'])
def token():
    base = request.host_url.rstrip('/')
    grant_type = request.form.get('grant_type') or request.json.get('grant_type')

    # A. Server-to-Server Data Sync Auth
    if grant_type == 'client_credentials':
        return jsonify({"access_token": "mock_api_token", "expires_in": 600, "token_type": "Bearer"})

    # B. SSO User Login Auth
    if grant_type == 'authorization_code':
        code = request.form.get('code')
        nonce = session_store.get(code, {}).get("nonce", "")

        id_token_payload = {
            "iss": f"{base}/oidc",
            "aud": request.form.get('client_id', 'mock_client'),
            "exp": int(time.time()) + 3600,
            "iat": int(time.time()),
            "sub": "7f31c1d2-58a0-mock-user-01",
            "email": "priya.mock@example.com",
            "name": "Priya Sharma",
            "nonce": nonce
        }
        
        # Sign the JWT exactly how Moodle expects it
        id_token = jwt.encode(id_token_payload, private_pem, algorithm="RS256", headers={"kid": "mock-key-1"})
        
        return jsonify({
            "access_token": "mock_user_token",
            "id_token": id_token,
            "expires_in": 3600,
            "token_type": "Bearer"
        })

@app.route('/oidc/me', methods=['GET'])
def userinfo():
    return jsonify({
        "sub": "7f31c1d2-58a0-mock-user-01",
        "email": "priya.mock@example.com",
        "email_verified": True,
        "name": "Priya Sharma",
        "given_name": "Priya",
        "family_name": "Sharma"
    })

# ==========================================
# SWAYAM REST API ENDPOINTS
# ==========================================

@app.route('/api/v1/partner/ping', methods=['GET'])
def ping():
    return jsonify({"ok": True, "clientId": "mock_client", "scopes": ["partner.enrollments:read", "partner.completions:write"]})

@app.route('/api/v1/partner/enrollments', methods=['GET'])
def get_enrollments():
    if request.args.get('page', 1, type=int) == 1:
        return jsonify({"enrollments": [{
            "enrollmentId": "mock-enrollment-999", "courseId": "ai-ml-using-python",
            "status": "IN_PROGRESS", "progressPercent": 40
        }]})
    return jsonify({"enrollments": []})

@app.route('/api/v1/partner/enrollments/<enrollment_id>/progress', methods=['POST'])
def progress(enrollment_id):
    return '', 204

@app.route('/api/v1/partner/enrollments/<enrollment_id>/completion', methods=['POST'])
def completion(enrollment_id):
    return '', 204

if __name__ == '__main__':
    print("Starting Swayam Mock Server on http://localhost:8000")
    app.run(port=8000)
