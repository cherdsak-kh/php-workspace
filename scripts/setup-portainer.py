#!/usr/bin/env python3
"""
Portainer CE Token & Setup Utility for PHP Workspace
Extracts setup token from logs, initializes admin user, generates persistent API token,
and saves credentials to portainer-credentials.txt and php/src/portainer-token.json
"""

import os
import re
import sys
import json
import time
import uuid
import subprocess
import urllib.request
import urllib.error

WORKSPACE_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
PORTAINER_URL = os.environ.get("PORTAINER_URL", "http://localhost:9000")
ADMIN_USER = os.environ.get("PORTAINER_ADMIN_USER", "admin")
ADMIN_PASS = os.environ.get("PORTAINER_ADMIN_PASSWORD", "AdminHub@2026!")

CREDENTIALS_FILE = os.path.join(WORKSPACE_ROOT, "portainer-credentials.txt")
TOKEN_JSON_FILE = os.path.join(WORKSPACE_ROOT, "php", "src", "portainer-token.json")

def is_portainer_container_running():
    """Check if portainer-hub container is running."""
    commands = [
        ["docker", "ps", "--filter", "name=portainer-hub", "--filter", "status=running", "-q"],
        ["wsl", "docker", "ps", "--filter", "name=portainer-hub", "--filter", "status=running", "-q"],
    ]
    for cmd in commands:
        try:
            res = subprocess.run(cmd, capture_output=True, text=True, timeout=3)
            if res.returncode == 0 and res.stdout.strip():
                return True
        except Exception:
            pass
    return False

def get_docker_logs():
    """Extract portainer container logs using docker or wsl docker."""
    try:
        proc = subprocess.run(["docker", "logs", "portainer-hub"], capture_output=True, text=True, timeout=5)
        if proc.returncode == 0:
            return proc.stdout + proc.stderr
    except Exception:
        pass
    
    try:
        proc = subprocess.run(["wsl", "docker", "logs", "portainer-hub"], capture_output=True, text=True, timeout=5)
        if proc.returncode == 0:
            return proc.stdout + proc.stderr
    except Exception:
        pass
    
    return ""

def restart_portainer():
    """Restart portainer container using docker or wsl docker."""
    try:
        subprocess.run(["docker", "restart", "portainer-hub"], capture_output=True, timeout=10)
        return
    except Exception:
        pass
    try:
        subprocess.run(["wsl", "docker", "restart", "portainer-hub"], capture_output=True, timeout=10)
    except Exception:
        pass

def extract_setup_token(logs):
    """Find setup_token in container logs."""
    tokens = re.findall(r"setup_token=([a-f0-9]{32,64})", logs)
    if tokens:
        return tokens[-1]
    return ""

def check_admin_exists():
    """Check if admin account is already initialized."""
    url = f"{PORTAINER_URL}/api/users/admin/check"
    try:
        req = urllib.request.Request(url, headers={"User-Agent": "PHP-Workspace-Hub"})
        with urllib.request.urlopen(req, timeout=3) as resp:
            return resp.status == 204 or resp.status == 200
    except urllib.error.HTTPError as e:
        if e.code == 404:
            return False # Admin does not exist
        return True # Other error or already initialized
    except Exception:
        return None

def init_admin(setup_token):
    """Initialize admin account using setup token."""
    url = f"{PORTAINER_URL}/api/users/admin/init"
    payload = json.dumps({"Username": ADMIN_USER, "Password": ADMIN_PASS}).encode("utf-8")
    headers = {
        "Content-Type": "application/json",
        "X-Setup-Token": setup_token
    }
    req = urllib.request.Request(url, data=payload, headers=headers, method="POST")
    try:
        with urllib.request.urlopen(req, timeout=10) as resp:
            return json.loads(resp.read().decode("utf-8"))
    except Exception as e:
        print(f"[!] Init admin failed: {e}")
        return None

def login_and_get_jwt():
    """Authenticate and obtain JWT bearer token."""
    url = f"{PORTAINER_URL}/api/auth"
    payload = json.dumps({"Username": ADMIN_USER, "Password": ADMIN_PASS}).encode("utf-8")
    headers = {"Content-Type": "application/json"}
    req = urllib.request.Request(url, data=payload, headers=headers, method="POST")
    try:
        with urllib.request.urlopen(req, timeout=10) as resp:
            data = json.loads(resp.read().decode("utf-8"))
            return data.get("jwt")
    except Exception as e:
        print(f"[!] Login failed: {e}")
        return None

def create_persistent_api_key(jwt_token):
    """Create a persistent API key for the admin user."""
    url = f"{PORTAINER_URL}/api/users/1/tokens"
    payload = json.dumps({"description": "php-workspace-key", "password": ADMIN_PASS}).encode("utf-8")
    headers = {
        "Content-Type": "application/json",
        "Authorization": f"Bearer {jwt_token}"
    }
    req = urllib.request.Request(url, data=payload, headers=headers, method="POST")
    try:
        with urllib.request.urlopen(req, timeout=10) as resp:
            data = json.loads(resp.read().decode("utf-8"))
            return data.get("rawAPIKey")
    except Exception as e:
        print(f"[!] API Key creation failed: {e}")
        return None

def register_local_endpoint(jwt_token):
    """Ensure the local Docker environment is registered in Portainer."""
    check_url = f"{PORTAINER_URL}/api/endpoints"
    headers = {"Authorization": f"Bearer {jwt_token}"}
    req = urllib.request.Request(check_url, headers=headers)
    try:
        with urllib.request.urlopen(req, timeout=5) as resp:
            endpoints = json.loads(resp.read().decode("utf-8"))
            if endpoints:
                return True # Already has endpoints
    except Exception:
        pass

    # Register local environment
    boundary = "----WebKitFormBoundary" + uuid.uuid4().hex
    form_headers = {
        "Authorization": f"Bearer {jwt_token}",
        "Content-Type": f"multipart/form-data; boundary={boundary}"
    }
    parts = [
        f"--{boundary}",
        'Content-Disposition: form-data; name="Name"',
        "",
        "local",
        f"--{boundary}",
        'Content-Disposition: form-data; name="EndpointCreationType"',
        "",
        "1",
        f"--{boundary}--",
        ""
    ]
    body = "\r\n".join(parts).encode("utf-8")
    create_req = urllib.request.Request(check_url, data=body, headers=form_headers, method="POST")
    try:
        with urllib.request.urlopen(create_req, timeout=10) as resp:
            return True
    except Exception:
        return False

def main():
    print("=" * 60)
    print("      Portainer CE Token & Setup Utility")
    print("=" * 60)

    # 1. First check if container is running
    if not is_portainer_container_running():
        print("[-] Portainer container 'portainer-hub' is currently STOPPED.")
        print("    Please start Portainer first (Option 5 in launcher).")
        return

    # 2. Check HTTP status (retry if container just booted up)
    admin_status = None
    for attempt in range(5):
        admin_status = check_admin_exists()
        if admin_status is not None:
            break
        print(f"[*] Waiting for Portainer to initialize at {PORTAINER_URL}...")
        time.sleep(2)

    if admin_status is None:
        print(f"[-] Cannot connect to Portainer at {PORTAINER_URL}")
        print("    Container is running, but port 9000 is not responding yet.")
        return

    logs = get_docker_logs()
    setup_token = extract_setup_token(logs)

    jwt_token = ""
    api_key = ""

    if admin_status is False:
        print("[*] Portainer admin not initialized yet.")
        for _ in range(5):
            if setup_token:
                break
            time.sleep(2)
            logs = get_docker_logs()
            setup_token = extract_setup_token(logs)

        if setup_token:
            print(f"[+] Found Setup Token from container logs: {setup_token[:16]}...")
            print(f"[*] Initializing admin account '{ADMIN_USER}'...")
            init_res = init_admin(setup_token)
            if init_res:
                print("[+] Admin account initialized successfully!")
            else:
                print("[-] Could not initialize admin automatically.")
        else:
            print("[-] Setup token not found in logs. Container might have timed out. Restarting...")
            restart_portainer()
            return
    else:
        print("[+] Admin account is already configured.")

    # Authenticate to get tokens
    print("[*] Authenticating with Portainer API...")
    jwt_token = login_and_get_jwt()

    if jwt_token:
        print("[+] Successfully authenticated (JWT Token obtained).")
        # Ensure local endpoint is mapped
        register_local_endpoint(jwt_token)
        # Create persistent API key
        print("[*] Generating persistent API Key...")
        api_key = create_persistent_api_key(jwt_token)
        if api_key:
            print(f"[+] API Key generated: {api_key[:16]}...")

    # Preserve old API key if available
    if not api_key and os.path.exists(TOKEN_JSON_FILE):
        try:
            with open(TOKEN_JSON_FILE, "r", encoding="utf-8") as f:
                old_data = json.load(f)
                if old_data.get("api_key"):
                    api_key = old_data.get("api_key")
        except Exception:
            pass

    # Save output files
    summary = {
        "portainer_url": PORTAINER_URL,
        "username": ADMIN_USER,
        "password": ADMIN_PASS,
        "setup_token": setup_token,
        "api_key": api_key,
        "jwt_token": jwt_token,
    }

    cred_text = f"""# ========================================================
# Portainer CE Credentials & Tokens
# Generated by php-workspace
# ========================================================

URL:         {PORTAINER_URL}
Username:    {ADMIN_USER}
Password:    {ADMIN_PASS}

Setup Token: {setup_token}
Persistent API Key (X-API-Key):
{api_key}

Bearer JWT Token:
{jwt_token}

# Example API usage:
# curl -H "X-API-Key: {api_key}" {PORTAINER_URL}/api/endpoints
"""
    with open(CREDENTIALS_FILE, "w", encoding="utf-8") as f:
        f.write(cred_text)
    print(f"\n[OK] Saved credentials to: {CREDENTIALS_FILE}")

    with open(TOKEN_JSON_FILE, "w", encoding="utf-8") as f:
        json.dump(summary, f, indent=2)
    print(f"[OK] Saved token JSON to: {TOKEN_JSON_FILE}")

    print("\n" + "=" * 60)
    print(f" Web UI:      {PORTAINER_URL}")
    print(f" Username:    {ADMIN_USER}")
    print(f" Password:    {ADMIN_PASS}")
    if api_key:
        print(f" API Key:     {api_key}")
    if setup_token:
        print(f" Setup Token: {setup_token}")
    print("=" * 60)

if __name__ == "__main__":
    main()
