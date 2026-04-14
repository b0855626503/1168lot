#!/usr/bin/env bash
set -u

MODE="${1:-check}"
TIMESTAMP="$(date +%Y%m%d-%H%M%S)"
LOG_DIR="${TMPDIR:-/tmp}"
LOG_FILE="${LOG_DIR}/wsl-doctor-${TIMESTAMP}.log"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

FAILURES=0
WARNINGS=0

print_header() {
    echo -e "${BLUE}== $1 ==${NC}"
}

ok() {
    echo -e "${GREEN}[OK]${NC} $1"
}

warn() {
    WARNINGS=$((WARNINGS + 1))
    echo -e "${YELLOW}[WARN]${NC} $1"
}

fail() {
    FAILURES=$((FAILURES + 1))
    echo -e "${RED}[FAIL]${NC} $1"
}

log() {
    printf "%s\n" "$1" >> "$LOG_FILE"
}

run_cmd() {
    local title="$1"
    shift

    print_header "$title"
    {
        echo "### $title"
        echo "$ $*"
        "$@"
        echo
    } >> "$LOG_FILE" 2>&1

    "$@" 2>/dev/null || true
}

check_path_contains_windows() {
    print_header "PATH Health"
    if echo "$PATH" | grep -Eqi "/mnt/c/(windows|WINDOWS)/(System32|system32)"; then
        ok "PATH มี Windows System32"
    else
        warn "PATH ไม่มี /mnt/c/Windows/System32 (interop command อาจหาไม่เจอ)"
    fi

    if command -v wslpath >/dev/null 2>&1; then
        ok "พบคำสั่ง wslpath"
    else
        warn "ไม่พบ wslpath"
    fi
}

check_mounts() {
    print_header "Mount Health"

    if mount | grep -q "on /mnt/c "; then
        ok "/mnt/c ถูก mount แล้ว"
    else
        fail "/mnt/c ไม่ได้ mount"
    fi

    if [ -d /mnt/c/Windows ]; then
        ok "เข้าถึง /mnt/c/Windows ได้"
    else
        fail "เข้า /mnt/c/Windows ไม่ได้"
    fi

    if mount | grep -q "aname=drvfs;path=C:"; then
        ok "C: ใช้ drvfs mount"
    else
        warn "ไม่พบ signature drvfs ของ C:"
    fi
}

check_interop() {
    print_header "Interop Health"

    if [ -r /proc/sys/fs/binfmt_misc/WSLInterop ]; then
        if grep -q "enabled" /proc/sys/fs/binfmt_misc/WSLInterop; then
            ok "WSLInterop enabled"
        else
            fail "WSLInterop ไม่ได้ enabled"
        fi
    else
        warn "อ่าน /proc/sys/fs/binfmt_misc/WSLInterop ไม่ได้"
    fi

    if /mnt/c/Windows/System32/cmd.exe /c ver >/dev/null 2>&1; then
        ok "เรียก cmd.exe จาก WSL ได้"
    else
        fail "เรียก cmd.exe จาก WSL ไม่ได้"
    fi
}

check_wsl_config() {
    print_header "WSL Config"

    if [ -f /etc/wsl.conf ]; then
        ok "พบ /etc/wsl.conf"
        if grep -Eq "^\s*enabled\s*=\s*false" /etc/wsl.conf; then
            warn "พบ automount.enabled=false (อาจทำให้ /mnt/c หาย)"
        fi
    else
        warn "ไม่พบ /etc/wsl.conf"
    fi
}

collect_context() {
    print_header "System Snapshot"
    run_cmd "Kernel" uname -a
    run_cmd "WSL distro" bash -lc "grep -E '^(NAME|VERSION)=' /etc/os-release"
    run_cmd "Mount list" mount
    run_cmd "Current PATH" bash -lc "echo \"$PATH\""
    run_cmd "Recent dmesg (wsl/drvfs/mount)" bash -lc "dmesg 2>/dev/null | egrep -i 'wsl|drvfs|9p|mount|fail|error' | tail -n 120"
}

attempt_fix() {
    print_header "Attempt Fix"

    if mount | grep -q "on /mnt/c "; then
        ok "/mnt/c ยัง mount อยู่ ไม่ต้อง remount"
    else
        warn "ลอง remount /mnt/c"
        if sudo mount -t drvfs C: /mnt/c >/dev/null 2>&1; then
            ok "remount /mnt/c สำเร็จ"
        else
            fail "remount /mnt/c ไม่สำเร็จ (ลอง wsl --shutdown จากฝั่ง Windows)"
        fi
    fi

    if ! echo "$PATH" | grep -Eqi "/mnt/c/(windows|WINDOWS)/(System32|system32)"; then
        warn "เติม Windows System32 เข้า PATH ชั่วคราวใน shell นี้"
        export PATH="$PATH:/mnt/c/WINDOWS/system32:/mnt/c/WINDOWS:/mnt/c/Windows/System32:/mnt/c/Windows"
        if /mnt/c/Windows/System32/cmd.exe /c ver >/dev/null 2>&1; then
            ok "เติม PATH แล้ว interop กลับมาใช้ได้"
        else
            warn "เติม PATH แล้ว แต่ยังเรียก cmd.exe ไม่ได้"
        fi
    fi
}

summary() {
    print_header "Summary"
    echo "Log file: $LOG_FILE"

    if [ "$FAILURES" -eq 0 ] && [ "$WARNINGS" -eq 0 ]; then
        echo -e "${GREEN}สถานะ: ปกติทั้งหมด${NC}"
        return 0
    fi

    if [ "$FAILURES" -eq 0 ]; then
        echo -e "${YELLOW}สถานะ: มี warning ${WARNINGS} รายการ${NC}"
        echo "แนะนำ: ถ้าเริ่มมีอาการ path หาย ให้รันใน PowerShell -> wsl --shutdown"
        return 0
    fi

    echo -e "${RED}สถานะ: มี failure ${FAILURES} รายการ, warning ${WARNINGS} รายการ${NC}"
    echo "แนะนำลำดับแก้ไข:"
    echo "1) PowerShell: wsl --shutdown"
    echo "2) เปิด WSL ใหม่แล้วรันสคริปต์ซ้ำ"
    echo "3) ถ้ายังไม่หาย: Restart-Service LxssManager -Force"
    return 1
}

main() {
    case "$MODE" in
        check)
            check_path_contains_windows
            check_mounts
            check_interop
            check_wsl_config
            collect_context
            summary
            ;;
        fix)
            check_path_contains_windows
            check_mounts
            check_interop
            check_wsl_config
            attempt_fix
            collect_context
            summary
            ;;
        *)
            echo "Usage: $0 [check|fix]"
            exit 2
            ;;
    esac
}

main "$@"
