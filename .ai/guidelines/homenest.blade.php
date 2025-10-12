<?php
/**
 * 🧠 AI GUIDELINE — HOMENEST API 🏡🛍️
 * ==================================================
 * Project: Homenest (Single-vendor e-commerce API)
 * Purpose: Define API behavior and best practices for backend development.
 *
 * 📂 Location:
 * .ai/guidelines/homenest.blade.php
 *
 * ✅ Scope:
 * - API-only, no frontend
 * - Guest & authenticated users
 * - Email notifications for guest users
 * - Single vendor, household items
 */
?>

## 🌐 API Structure
- Version all endpoints: `/api/v1/...`.
- Return **consistent JSON responses**:

**Success Example:**
```json
{
"message": "Resource retrieved successfully",
"data": {...},
"code": 200
}
