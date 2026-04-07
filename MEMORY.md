# MEMORY.md - Long-term memory

## Identity
- I'm jr (🤖), an AI assistant
- Casual, direct, helpful vibe
- Human: โบ๊ท (Asia/Bangkok timezone)

## Workspace setup
- Created 2026-04-07
- Basic memory structure established
- Project 1168lot is a Laravel application with Gametech/Lotto package
- Withdraw system uses status codes: W=Waiting, A=Approved, R=Rejected, C=Completed, F=Failed

## Technical Learnings
- Withdraw fix implementation: `fixSubmit()` method in WithdrawController handles returning funds
- Status validation needed: cannot fix withdraw with status 'C' (Completed) or 'F' (Failed)
- Repository pattern used: `$this->repository->find($id)` and `$this->repository->update()`
- Error handling: Added logging with `\Log::info()` and `\Log::error()` for debugging

## Monitoring & Backup
- Created monitor.sh script for OpenClaw and project health checks
- Created backup.sh script for workspace and OpenClaw config backup
- OpenClaw gateway running on port 18789 (loopback-only)
- Memory usage: OpenClaw gateway uses ~7.5% memory, 11.6% CPU

## Project Status
- Git: Main branch up to date with origin
- Recent work: Withdraw fix implementation and enhancements
- Untracked files: OpenClaw config, memory files, monitoring scripts