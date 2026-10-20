import os
import sys
import time
import secrets
from pathlib import Path

import mss
import mss.tools
import requests

BASE_URL = "http://localhost/kurbadaV2/Kurbada/api"
PENDING_URL = f"{BASE_URL}/get_pending.php"
UPLOAD_URL = f"{BASE_URL}/upload_image.php"

TOKEN = os.environ.get("KURBADA_UPLOAD_TOKEN", "jlx2027")

POLL_SECONDS = 1
CAMERA_DELAY = 1.5    
MONITOR_INDEX = 1      
MAX_ATTEMPTS = 3

OUTPUT_DIR = Path.home() / "Documents" / "Kurbada"
OUTPUT_DIR.mkdir(parents=True, exist_ok=True)

if not TOKEN:
    sys.exit("Missing KURBADA_UPLOAD_TOKEN environment variable.")

HEADERS = {"X-API-Token": TOKEN}
attempts = {}


def take_screenshot() -> Path:
    filename = f"accident_{secrets.token_hex(8)}.png"
    path = OUTPUT_DIR / filename

    with mss.mss() as screen:
        shot = screen.grab(screen.monitors[MONITOR_INDEX])
        mss.tools.to_png(shot.rgb, shot.size, output=str(path))

    return path


def upload(accident_id: int, path: Path) -> bool:
    with path.open("rb") as f:
        response = requests.post(
            UPLOAD_URL,
            headers=HEADERS,
            data={"accident_id": str(accident_id)},
            files={"image": (path.name, f, "image/png")},
            timeout=30,
        )

    print(f"[KURBADA] Upload status {response.status_code}: {response.text}")
    return response.status_code == 200


def handle(accident: dict) -> None:
    accident_id = int(accident["id"])
    attempts[accident_id] = attempts.get(accident_id, 0) + 1

    if attempts[accident_id] > MAX_ATTEMPTS:
        return

    print(f"[KURBADA] New accident #{accident_id} ({accident['severity']}) - capturing...")
    time.sleep(CAMERA_DELAY)

    path = take_screenshot()
    print(f"[KURBADA] Screenshot saved: {path}")
    upload(accident_id, path)


def main() -> None:
    print("[KURBADA] Capture helper running. Press Ctrl+C to stop.")

    while True:
        try:
            response = requests.get(PENDING_URL, headers=HEADERS, timeout=10)

            if response.status_code == 401:
                print("[KURBADA] Wrong token. Check KURBADA_UPLOAD_TOKEN.")
            elif response.ok:
                data = response.json()
                if data.get("pending"):
                    handle(data["accident"])
            else:
                print("[KURBADA] Pending check failed:", response.status_code)

        except requests.RequestException as error:
            print("[KURBADA] Cannot reach website:", error)
        except Exception as error:
            print("[KURBADA] Error:", error)

        time.sleep(POLL_SECONDS)


if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        print("\n[KURBADA] Stopped.")
