# PROMPT GUIDE

Version: 1.0

Project

KJA Event Manager

---

# Purpose

Panduan resmi memberikan task kepada AI.

Setiap prompt harus jelas, memiliki scope, dan acceptance criteria.

---

# Prompt Types

## Audit

Purpose

Analyze only.

No code changes.

Output

Markdown report.

---

## Design

Purpose

Design architecture.

No implementation.

Output

Documentation.

---

## Refactor

Purpose

Improve existing code.

No behavior change.

---

## Feature

Purpose

Implement new feature.

Must follow TASK_TEMPLATE.md.

---

## Bug Fix

Purpose

Fix bug only.

No unrelated refactor.

---

## Review

Purpose

Analyze implementation.

No code generation.

---

## Documentation

Purpose

Update documentation only.

---

# Standard Prompt Structure

Task

↓

Goal

↓

Background

↓

Read Documentation

↓

Scope

↓

Constraints

↓

Acceptance Criteria

↓

Output

---

# Example Prompt

Task

Implement Attendance Code

Goal

Replace NIP-based QR with Attendance Code.

Scope

Attendance Module only.

Constraints

Do not modify Registration.

Do not rename Model.

Acceptance Criteria

Attendance Code generated.

QR works.

Backward compatible.

Documentation updated.

---

# AI Response Format

Summary

Plan

Changed Files

Testing

Documentation

Known Limitation

Next Recommendation

---

# Prompt Rules

Always

- One goal
- One feature
- One commit
- One documentation update

Never

- Multiple unrelated features
- Refactor + New Feature together
- Architecture redesign without approval

---

# Token Optimization

Read only related documentation.

Use

CURRENT_STATE.md

INDEX.md

SERVICE_PLAN.md

Avoid scanning whole repository.

---

# Common Tasks

Audit UI

Audit Service

Implement Feature

Fix Bug

Refactor Service

Generate Test

Generate Export

Generate Report

Update Documentation

Review Pull Request

---

# AI Checklist

Before coding

☐ Read documentation

☐ Understand scope

☐ Check Service Plan

☐ Check Enum Plan

☐ Check Current State

After coding

☐ Test

☐ Update docs

☐ Generate summary

☐ Ready for review