# AI WORKFLOW

Version: 1.0

Project

KJA Event Manager

Status

Official Workflow

---

# Purpose

Dokumen ini mendefinisikan bagaimana AI harus bekerja di dalam project ini.

Semua AI Assistant (OpenCode, Codex, GPT, Claude, dll.) wajib mengikuti workflow ini.

AI tidak boleh langsung menulis kode tanpa memahami konteks proyek.

---

# AI Principles

- Think before coding.
- Read documentation first.
- Change only related files.
- Keep backward compatibility.
- Minimize unnecessary changes.
- Document every architectural change.

---

# Documentation Reading Order

Before doing ANY task, AI must read:

1. CURRENT_STATE.md
2. CONTEXT.md
3. INDEX.md
4. AGENTS.md
5. PROJECT_MANIFEST.md

Then continue based on task.

---

# Module Reading

If task is Attendance:

Read

FEATURE.md

DATABASE.md

SERVICE_PLAN.md

ENUM_PLAN.md

---

If task is Registration

Read related documentation only.

Never scan entire repository.

---

# Task Workflow

User Request

↓

Understand Goal

↓

Read Documentation

↓

Analyze Existing Code

↓

Create Plan

↓

Wait (if architecture change required)

↓

Implement

↓

Test

↓

Update Documentation

↓

Done

---

# Architecture Changes

If task changes:

Database

Architecture

Core Flow

Security

AI MUST NOT implement immediately.

Generate proposal first.

Wait for approval.

---

# Allowed Without Approval

- Bug Fix
- UI Fix
- Validation Improvement
- Performance Optimization
- Refactor inside same Service
- Unit Test
- Documentation

---

# Need Approval

- Migration
- Rename Model
- Rename Table
- Delete Feature
- Delete Column
- Change Core Flow
- Breaking Change

---

# Coding Rules

Always

- Use existing Service
- Use existing Enum
- Use Config
- Follow Coding Standards

Never

- Hardcode
- Duplicate Logic
- Duplicate Component
- Duplicate Query

---

# Documentation Update

When feature changes

Update

FEATURE.md

CHANGELOG.md

TODO.md

If architecture changes

DATABASE.md

ARCHITECTURE.md

DECISION.md

---

# Commit Scope

One Task

↓

One Commit

Do not combine unrelated features.

---

# Review Checklist

Before finishing task

Check

- Validation
- Responsive
- Dark Mode
- Documentation
- Enum
- Service
- Test

---

# AI Priority

Priority Order

1. Stability
2. Correctness
3. Readability
4. Reusability
5. Performance

Never sacrifice stability for optimization.

---

# Token Optimization

Prefer

Read only related files.

Avoid

Reading whole repository.

Use

INDEX.md

FILEMAP.md

SERVICE_PLAN.md

To understand structure quickly.

---

# Refactoring Strategy

Incremental Refactoring.

Never refactor whole project.

Rule

Touch Feature

↓

Improve Feature

↓

Move Logic

↓

Update Docs

---

# Error Handling

If AI is unsure

Do not guess.

Explain uncertainty.

Ask for clarification.

---

# Definition of Success

A task is successful when:

- Requirements completed
- No regression
- Documentation updated
- Existing feature still works
- Code follows Coding Standards

---

# AI Goal

AI is a development assistant.

AI should improve the project step by step.

AI must never redesign the project without approval.