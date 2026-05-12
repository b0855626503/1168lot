# Product Definition — 1168lot

## Project Name

**1168lot** — Gametech Lotto Platform

## Description

Lottery platform with full lifecycle management — betting, draw lifecycle, settlement/payout, payment integrations, and real-time results for both operators and players.

## Problem Statement

พัฒนาระบบหวยสมัยใหม่ที่ยืดหยุ่นและต่อขยายได้ง่าย รองรับการเพิ่มตลาดใหม่และหลากหลายช่องทางชำระเงิน พร้อมโครงสร้าง API ที่ช่วยผู้ประกอบการจัดการการออกรางวัล การรับแทง และการจ่ายรางวัลอย่างปลอดภัยและมีประสิทธิภาพ

## Target Users

**Both operators and players:**

- **Operators (Admin):** Run draws, manage members, configure markets/payment channels, monitor financials, handle corrections
- **Players (Members):** Place bets, check results, manage wallets, deposit/withdraw, view history

## Key Goals

1. **Financial correctness and auditability** — stable payout calculation, never a missing settlement, audit-complete financial trails via `wallet_transactions`
2. **Platform extensibility** — new markets, bet types, payment channels can be added without rewrites; package-based architecture under `packages/Gametech/`
3. **Operational reliability and real-time** — reliable auto-result fetching via Horizon queues, real-time WebSocket broadcast for draw results and ticket updates
