# 🚀 Poster SaaS - Product Specification

## 📌 Overview

A simple SaaS platform for Indian small businesses to generate ready-made marketing posters using predefined templates and AI image generation (Gemini API).

The system focuses on **speed, simplicity, and zero design effort**.

---

# 🎯 Target Audience

* Small business owners across India
* Non-tech users (basic smartphone users)
* दुकानदार, salon owners, clothing shops, etc.

---

# 🧠 Core Value Proposition

> "10–20 seconds me ready marketing poster generate karo — bina design skills ke"

---

# 🔁 User Flow

```
Login → Select Template → Fill Business Details → Generate Poster → Download / Share
```

---

# 🔐 Authentication System

## Login Method:

* Phone Number + Password

## Behavior:

* User registers once
* Business data saved in DB
* Next time → auto-filled form

---

# 🧾 User Input Fields

| Field         | Required     |
| ------------- | ------------ |
| Business Name | ✅            |
| Business Type | ✅            |
| Phone Number  | ✅            |
| Logo Upload   | ❌ (Optional) |

---

# 🎨 Template System (Core Engine)

## Admin Panel Required ✅

### Admin Can:

* Add new templates
* Upload preview image
* Add prompt for AI generation

---

## Template Structure

Each template contains:

```json
{
  "id": 1,
  "name": "Diwali Sale Poster",
  "preview_image": "preview.jpg",
  "prompt": "Generate a Diwali sale poster with {business_name}, {offer}, festive lights, indian theme",
  "category": "Festival"
}
```

---

## Template Behavior

* Templates are **static**
* Prompt is predefined by admin
* User input dynamically injected into prompt

---

# ⚙️ Poster Generation System

## AI Engine:

* Gemini API (image generation)

## Flow:

1. User selects template
2. System replaces variables:

   * {business_name}
   * {business_type}
   * {phone}
3. Final prompt sent to Gemini API
4. Image generated
5. Display to user

---

# 📂 Categories

* Festival (API-based auto fetch in future)
* Sale / Discount
* Product Showcase
* Announcement

---

# 🖼️ Output System

## Format:

* PNG only
* Instagram size (1:1)

## Actions:

* Download button
* WhatsApp share button

---

# 💾 Database Design (SQLite)

## Users Table

```sql
users (
  id INTEGER PRIMARY KEY,
  phone TEXT UNIQUE,
  password TEXT,
  created_at DATETIME
)
```

---

## Business Profile Table

```sql
business_profiles (
  id INTEGER PRIMARY KEY,
  user_id INTEGER,
  business_name TEXT,
  business_type TEXT,
  phone TEXT,
  logo_path TEXT,
  FOREIGN KEY(user_id) REFERENCES users(id)
)
```

---

## Templates Table

```sql
templates (
  id INTEGER PRIMARY KEY,
  name TEXT,
  preview_image TEXT,
  prompt TEXT,
  category TEXT,
  created_at DATETIME
)
```

---

## Generated Posters Table (optional but recommended)

```sql
posters (
  id INTEGER PRIMARY KEY,
  user_id INTEGER,
  template_id INTEGER,
  image_url TEXT,
  created_at DATETIME
)
```

---

# 🧑‍💻 Tech Stack

## Frontend:

* HTML
* Tailwind CSS

## Backend:

* PHP

## Database:

* SQLite

## Hosting:

* InfinityFree

## AI:

* Gemini API

---

# 🧩 Admin Panel Features

## Required Modules:

* Login (admin only)
* Add Template
* Upload Preview Image
* Add Prompt
* Assign Category
* View Templates List

---

# ⚡ Key Product Principles

## 1. Zero Complexity

* No design tools
* No drag-drop
* Only selection + generate

## 2. Speed First

* Max 10–20 sec generation acceptable

## 3. Mobile First UI

* Large buttons
* Simple layout

---

# 🚀 Launch Strategy

## Phase 1:

* 10–15 templates
* Manual testing
* Local demo

## Phase 2:

* Add more templates
* Improve prompts
* Optimize generation speed

---

# 📈 Growth Strategy

## Offline:

* Visit shops
* Demo live generation
* Show "before vs after"

## Online:

* Instagram page with daily posters
* Show real use cases

---

# 🔮 Future Scope (Optional)

* Festival auto-detection API
* Caption generator
* WhatsApp automation
* Multi-language support (Hindi)

---

# ⚠️ Constraints

* No freemium/premium (currently free)
* No AI text generation
* No analytics/dashboard complexity

---

# ✅ MVP Definition

A working version is complete if:

* User can login
* Admin can add templates
* User selects template
* Poster generates using Gemini
* User downloads image

---

# 🧠 Final Note

This is NOT a design tool.

This is a:

> "Pre-built marketing engine for non-tech users"

Keep it simple → it will sell.
