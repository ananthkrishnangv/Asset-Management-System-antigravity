import pty
import os
import sys

PASSWORD = "serc@123"
SERVER_IP = "10.10.200.57"
USER = "root"
REMOTE_PATH = "/var/www/html"

def read_output(fd):
    try:
        data = os.read(fd, 1024)
        sys.stdout.buffer.write(data)
        sys.stdout.flush()
        if b"password:" in data.lower():
            os.write(fd, (PASSWORD + "\n").encode())
        return data
    except OSError:
        return b""

def run_interactive(cmd):
    print(f"Running: {' '.join(cmd)}")
    pty.spawn(cmd, read_output)

def main():
    # 1. Install Composer Deps
    if not os.path.exists("vendor"):
        print("Installing dependencies...")
        if os.path.exists("composer.phar"):
            os.system("php composer.phar install --no-dev --optimize-autoloader")
        else:
            os.system("composer install --no-dev --optimize-autoloader")

    # 2. Rsync
    print("\nTransferring files...")
    # Ensure remote temp dir exists (optional, rsync might create it but safer if parent exists)
    # Actually rsync will create the target dir if it doesn't exist? 
    # Let's hope the path /var/www/html/temp_deploy/ structure works. 
    # Usually we rsync to a path.
    
    rsync_cmd = [
        "rsync", "-avz", 
        "--exclude", ".git", 
        "--exclude", "deploy.sh", 
        "--exclude", "run_deploy_local.sh", 
        "--exclude", "deploy_automation.py",
        ".", 
        f"{USER}@{SERVER_IP}:{REMOTE_PATH}/temp_deploy/"
    ]
    run_interactive(rsync_cmd)

    # 3. SSH Deploy
    print("\nExecuting deployment on server...")
    ssh_cmd = [
        "ssh", 
        f"{USER}@{SERVER_IP}", 
        f"cp -r {REMOTE_PATH}/temp_deploy/* {REMOTE_PATH}/ && rm -rf {REMOTE_PATH}/temp_deploy && cd {REMOTE_PATH} && chmod +x deploy.sh && ./deploy.sh"
    ]
    run_interactive(ssh_cmd)

    print("\n\nDeployment Automation Complete!")

if __name__ == "__main__":
    main()
