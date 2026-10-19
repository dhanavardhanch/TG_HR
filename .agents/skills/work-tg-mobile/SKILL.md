---
name: work-tg-mobile
description: |
  Switch context to work on TG_HR_Mobile_App (E:\TG_HR_Mobile\TG_HR_Mobile_App), including Flutter Kiosk mode, ArcFace 192-d facial recognition, offline SQLite sync, and UI.
---

# Work on TG_HR_Mobile_App Skill

Activate this skill when the user asks to "work on TG_HR_Mobile_APP", work on Flutter, adjust Kiosk mode, tune facial recognition, modify UI screens, or handle offline sync.

## Base Directory
All mobile app work is centered in:
`E:\TG_HR_Mobile\TG_HR_Mobile_App`

## Core Responsibilities & Workflows
1. **Kiosk Face Recognition**:
   - Face detector: Google ML Kit (`lib/ai/face_detector.dart`).
   - Feature extractor: MobileFaceNet 192-d float embeddings with 5-point affine alignment (`lib/ai/face_recognizer.dart`).
   - Matcher: Cosine similarity with 3-frame consensus voting buffer (`lib/ai/face_matcher.dart`).
   - UI: Fullscreen camera, red "Not Recognised" countdown, already punched overlay (`lib/screens/02_kiosk/ui/kiosk_screen.dart`).
2. **Offline-First Storage**:
   - SQLite database (`lib/core/database/app_database.dart`) caches employees and 192-d embeddings.
   - Offline punch queue synchronizes automatically when online (`lib/screens/05_sync_diagnostics/services/sync_service.dart`).
3. **Backend URL Integration**:
   - API endpoints defined in `lib/constants/api_endpoints.dart`.
   - Update `baseUrl` when Cloudflare Tunnel or BigRock production URL changes.

