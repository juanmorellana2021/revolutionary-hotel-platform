# 🎓 VICKY AI TRAINING SYSTEM GUIDE

## 📋 Overview

Sistema completo para entrenar a **Vicky** (AI Travel Agent) con preguntas y respuestas específicas sobre hoteles, tours, retiros y servicios de viaje.

---

## 🏗️ Architecture

```
┌─────────────────┐
│   User Query    │
└────────┬────────┘
         │
         ▼
┌─────────────────────────────┐
│  ai_trained_search.php      │
│  - Check training data      │
│  - Match keywords           │
│  - Calculate confidence     │
└────────┬────────────────────┘
         │
    ┌────┴─────┐
    │          │
    ▼          ▼
┌──────────┐  ┌──────────────────┐
│ Direct   │  │ Enhanced Prompt  │
│ Answer   │  │ + AI Generation  │
│ (70%+)   │  │ (Ollama)         │
└──────────┘  └──────────────────┘
```

---

## 📁 Files Created

### 1. **ai_training_data.json**
Base de datos de entrenamiento con 25+ ejemplos iniciales.

**Structure:**
```json
{
  "training_dataset": [
    {
      "question": "User question",
      "answer": "Vicky's response (max 15 words)",
      "category": "amenities|price|destination|tours...",
      "keywords": ["keyword1", "keyword2"]
    }
  ],
  "system_prompt": "Vicky's personality and rules",
  "response_templates": {}
}
```

### 2. **ai_trained_search.php**
Motor de búsqueda inteligente con dos modos:

**Mode 1: Direct Match (High Confidence)**
- Busca en training_data usando keywords
- Si confidence > 70%, usa respuesta entrenada
- No llama a Ollama (respuesta instantánea)

**Mode 2: AI Enhanced**
- Construye prompt con ejemplos
- Llama a Ollama con contexto
- Respuestas más naturales

**API:**
```php
POST /ai_trained_search.php
{
  "query": "user question"
}

Response:
{
  "response": "AI answer",
  "method": "training_direct|ai_enhanced|ai_error",
  "confidence": 85,
  "category": "amenities"
}
```

### 3. **ai_training_manager.html**
Interface visual para gestionar el entrenamiento:

**Features:**
- ➕ Agregar nuevas preguntas/respuestas
- 📊 Ver estadísticas (total, categorías)
- 🗑️ Eliminar preguntas
- 🧪 Probar respuestas en tiempo real
- 📚 Ver lista completa de entrenamiento

**Access:**
```
http://localhost/revolutionary-hotel-platform-github/ai_training_manager.html
```

### 4. **save_training_data.php**
Backend para guardar cambios:

**Features:**
- Valida estructura de datos
- Crea backups automáticos (últimos 5)
- Retorna confirmación
- Manejo de errores

### 5. **Updated public_booking.php**
Integrado con sistema de entrenamiento:

**Changes:**
- Llama a `ai_trained_search.php` en lugar de proxy directo
- Muestra confidence score
- Indica si es respuesta entrenada (🎓) o generada (🤖)

---

## 🎯 Categories

| Category | Descripción | Ejemplos |
|----------|-------------|----------|
| `amenities` | Servicios del hotel | piscina, wifi, spa |
| `price` | Consultas de precio | barato, presupuesto |
| `destination` | Ubicaciones | Cusco, Lima, playa |
| `tours` | Tours y excursiones | Machu Picchu, Valle Sagrado |
| `retreat` | Retiros | yoga, meditación, wellness |
| `room_type` | Tipos de habitación | familiar, suite, doble |
| `location` | Ubicación específica | centro, aeropuerto, playa |
| `packages` | Paquetes turísticos | todo incluido, combo |
| `special` | Ocasiones especiales | luna de miel, romántico |
| `rating` | Calificación | 5 estrellas, lujo |
| `services` | Servicios adicionales | traslado, transporte |
| `booking` | Reservaciones | cancelar, confirmar |
| `promotions` | Ofertas | descuento, promoción |
| `activities` | Actividades | rafting, trekking |
| `general` | Consultas generales | horarios, clima |

---

## 🚀 How to Use

### Add New Training Data

#### Method 1: Via Web Interface (Recommended)
1. Open `http://localhost/.../ai_training_manager.html`
2. Fill in the form:
   - **Question**: What user might ask
   - **Answer**: Vicky's response (max 15 words)
   - **Category**: Select from dropdown
   - **Keywords**: Comma-separated (include Spanish & English)
3. Click "💾 Guardar Nueva Pregunta"

#### Method 2: Direct JSON Edit
```json
{
  "question": "¿Tienen habitaciones con jacuzzi?",
  "answer": "Buscaré hoteles con jacuzzi en la habitación.",
  "category": "amenities",
  "keywords": ["jacuzzi", "tina", "hidromasaje", "bathtub"]
}
```

### Test AI Responses

**Via Training Manager:**
1. Go to "🧪 Probar Vicky" section
2. Enter test query
3. See response with:
   - Method used (training_direct/ai_enhanced)
   - Confidence score
   - Category matched

**Via Public Booking:**
1. Open `public_booking.php`
2. Type question in AI search box
3. Vicky responds with trained knowledge

---

## 💡 Best Practices

### Writing Questions
✅ **DO:**
- Use natural language users would type
- Include variations (formal/informal)
- Add Spanish AND English keywords
- Be specific to travel/hotels

❌ **DON'T:**
- Use overly technical terms
- Make answers too long (>15 words)
- Duplicate similar questions
- Include unrelated topics

### Writing Answers
✅ **DO:**
- Keep under 15 words
- Be friendly and helpful
- Include actionable response
- Use consistent tone

❌ **DON'T:**
- Give vague answers
- Include prices (use "consulta disponibilidad")
- Make promises ("garantizamos")
- Use jargon

### Keywords Strategy
```
Question: "¿Tienen hotel económico en Lima?"

Good Keywords:
["económico", "barato", "cheap", "budget", "lima", "precio"]

Why:
- Spanish + English
- Synonyms included
- Location included
- Related terms
```

---

## 📊 Confidence Scoring

| Score | Method | Description |
|-------|--------|-------------|
| 90-100% | Direct | Perfect keyword match |
| 70-89% | Direct | Good keyword match |
| 50-69% | AI Enhanced | Partial match + AI |
| <50% | AI Enhanced | Pure AI generation |

---

## 🔧 Advanced Configuration

### Adjust Temperature
In `ai_trained_search.php`:
```php
'temperature' => 0.3  // Lower = more focused
                     // Higher = more creative
```

### Change Confidence Threshold
```php
if ($highestScore > 15) {  // Adjust this number
    return match;
}
```

### Modify Max Response Length
```php
'num_predict' => 50  // Tokens (~10-15 words)
```

---

## 📈 Analytics

Training logs saved to: `ai_training_log.txt`

**Format:**
```json
{
  "timestamp": "2025-10-31 14:30:00",
  "query": "hotel con piscina",
  "response": "Buscaré hoteles con piscina.",
  "method": "training_direct",
  "confidence": 85,
  "category": "amenities"
}
```

**Use for:**
- Identify common queries
- Find low-confidence answers
- Improve training data
- Monitor AI performance

---

## 🎓 Training Examples Included

### Initial Dataset (25 examples):

1. **Amenities**: piscina, wifi, spa, desayuno, mascotas
2. **Price**: barato, económico, costo, precio
3. **Destination**: Cusco, Lima, playa
4. **Tours**: Machu Picchu, Valle Sagrado, city tour
5. **Retreats**: yoga, meditación, wellness
6. **Location**: centro, aeropuerto, playa
7. **Services**: traslado, transporte
8. **Special**: romántico, luna de miel, ecológico
9. **General**: mejor época para viajar

---

## 🚀 Deployment to Production

### Upload Files:
```bash
# From local to server
scp ai_training_data.json root@108.175.12.152:/var/www/html/manage/
scp ai_trained_search.php root@108.175.12.152:/var/www/html/manage/
scp save_training_data.php root@108.175.12.152:/var/www/html/manage/
scp ai_training_manager.html root@108.175.12.152:/var/www/html/manage/
scp public_booking.php root@108.175.12.152:/var/www/html/manage/
```

### Set Permissions:
```bash
chmod 644 ai_training_data.json
chmod 755 ai_trained_search.php save_training_data.php
```

### Test:
```
http://108.175.12.152/manage/ai_training_manager.html
```

---

## 🐛 Troubleshooting

### AI not responding
1. Check Ollama server: `http://72.60.1.16:11434`
2. Verify `ai_trained_search.php` has correct URL
3. Check browser console for errors

### Training data not saving
1. Check file permissions on `ai_training_data.json`
2. Verify PHP has write access
3. Check error logs

### Low confidence scores
1. Add more keywords to training data
2. Include synonyms (Spanish + English)
3. Add more example questions

---

## 📚 Future Enhancements

- [ ] Multi-language support (English, Portuguese)
- [ ] AI learns from user feedback
- [ ] Integration with booking system
- [ ] Voice input support
- [ ] Sentiment analysis
- [ ] Automated training suggestions

---

## 📞 Support

For issues or questions:
- Review training logs: `ai_training_log.txt`
- Test via: `ai_training_manager.html`
- Check console errors in browser

---

**Created:** October 31, 2025  
**Version:** 1.0  
**Author:** AI Training System for Revolutionary Hotel Platform
