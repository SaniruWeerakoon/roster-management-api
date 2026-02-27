# Roster Scheduling API (Laravel Backend)

This repository contains the **backend API and domain logic** for the Monthly Roster / Shift Scheduling system.

The backend is responsible for:
- Persisting roster data
- Enforcing data integrity
- Evaluating scheduling constraints
- Reporting violations to the client

The backend **does not perform auto-scheduling** and **does not mutate data during validation**.

---

## Overview

The system manages **monthly rosters** where:
- Each roster represents one calendar month
- Assignments are explicit and manually controlled
- Multiple people may be assigned to the same shift on the same day
- All scheduling decisions remain under human control

The backend exposes **bulk-friendly, stateless APIs** designed to support optimistic UIs.

---

## Architecture

- **Framework:** Laravel
- **API Style:** JSON, stateless
- **Database:** Relational (MySQL / PostgreSQL compatible)
- **Date Handling:** Carbon
- **Date Storage:** DATE only (YYYY-MM-DD)
- **Validation:** Read-only constraint evaluation

---

## Core Data Models

### Roster

Represents a single calendar month.

Key fields
- id
- month (DATE, normalized to first day of month)
- name

---

### Assignment

Represents one person assigned to one shift on one date.

(roster_id, date, shift_type_id, person_id)

Properties
- date stored as DATE (YYYY-MM-DD)
- Multiple people per (date, shift_type) are allowed
- Unique constraint prevents duplicate (roster, date, shift, person) rows

Not enforced (by design)
- No maximum number of people per shift
- No maximum number of shifts per day

---

### ShiftType

Defines a type of shift available in a roster.

Key fields
- id
- code (e.g. OPD, NIGHT)
- weight (used for workload calculations)

Shift types are enabled per roster via a pivot relationship.

---

### Person

Represents an assignable individual.

Key fields
- id
- code
- name

---

## API Endpoints

### Bulk Upsert Assignments

POST /api/rosters/{roster}/assignments/upsert

Purpose
- Create or update multiple assignments in one request
- Designed for optimistic UI batching

Request body
{
  "assignments": [
    {
      "date": "2026-01-04",
      "person_id": 4,
      "shift_type_id": 2
    }
  ]
}

Behavior
- Validates roster and enabled shift types
- Uses database-level upsert
- Idempotent for identical payloads

---

### Bulk Delete Assignments

POST /api/rosters/{roster}/assignments/delete

Purpose
- Remove multiple assignments in one request
- Used for batch remove / undo operations

Request body
{
  "assignments": [
    {
      "date": "2026-01-04",
      "person_id": 4,
      "shift_type_id": 2
    }
  ]
}

---

### Validate Roster

POST /api/rosters/{roster}/validate

Purpose
- Evaluate the roster against configured constraints
- Does not modify any data

---

## Validation Engine

Validation is implemented via a dedicated ConstraintService.

Characteristics
- Read-only
- Deterministic
- Stateless
- Safe to call repeatedly

---

## Implemented Validation Rules

- Availability conflicts
- Maximum total shifts per person (monthly)
- Incompatible shift combinations on the same day

---

## Design Principles

- Validation never mutates data
- No automatic scheduling or correction
- Calendar dates are not timestamps
- Bulk APIs > chatty endpoints
- Backend is source of truth, UI decides actions

---

## Environment & Setup

composer install
php artisan migrate
php artisan serve

---

## Current Status

- Assignment persistence
- Bulk upsert / delete APIs
- Constraint-based validation
- Structured violation reporting

---

## Deferred Work

- CSV / XLSX export endpoints
- Hard enforcement modes
- Historical roster snapshots
