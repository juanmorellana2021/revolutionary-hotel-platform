# 🚀 SISTEMA AI OPTIMIZADO PARA MULTI-HOTEL

## ✅ OPTIMIZACIONES IMPLEMENTADAS

### 1️⃣ **CACHE INTELIGENTE**
- ✅ Cache por hotel (5 minutos TTL)
- ✅ Auto-invalidación cuando cambian bookings
- ✅ Estadísticas de uso de cache
- ✅ Limpieza automática de cache expirado

### 2️⃣ **CONSULTAS OPTIMIZADAS**
- ✅ 1 sola query SQL (antes eran 3+)
- ✅ Subquery para ocupancy (más rápido que JOIN)
- ✅ Solo datos necesarios (no traemos fotos, amenities innecesarios)
- ✅ Índices en hotel_id y room_id

### 3️⃣ **AI ROUTING INTELIGENTE**
- ✅ **Queries simples** → tinyllama (0.5s respuesta)
- ✅ **Queries complejas** → qwen2.5:1.5b (1-2s respuesta)
- ✅ Timeout corto (3s simple, 8s complejo)
- ✅ Keep-alive 10min para múltiples hoteles

### 4️⃣ **CONTEXTO COMPACTO**
- ✅ Antes: ~500 palabras por query
- ✅ Ahora: ~100 palabras
- ✅ Solo estadísticas agregadas
- ✅ Detalles solo cuando se necesitan

---

## 📊 PERFORMANCE ESPERADO

### Escenario: 10 hoteles, 200 habitaciones cada uno

| Métrica | Sin Optimización | Con Optimización | Mejora |
|---------|------------------|------------------|--------|
| Query BD | ~300ms | ~50ms (cached) | **6x más rápido** |
| AI Response | ~2s | ~0.5-1s | **2x más rápido** |
| Memoria | ~5MB/hotel | ~500KB/hotel | **10x menos** |
| Requests/min | ~30 | ~200+ | **6x más requests** |

---

## 🔧 CÓMO FUNCIONA

### Flujo Optimizado:

```
Usuario pregunta: "habitaciones disponibles"
    ↓
1. ¿Está en cache? (hotel_id: 5)
    ↓ SI → Usar cache (0ms BD)
    ↓ NO → Query BD + Cache (50ms)
    ↓
2. ¿Query simple o compleja?
    ↓ Simple ("disponibles") → tinyllama
    ↓ Compleja ("suites premium bajo $200") → qwen2.5
    ↓
3. Contexto compacto (100 palabras vs 500)
    ↓
4. AI responde en 0.5-2 segundos
    ↓
5. Filtrar y mostrar resultados
```

---

## 🎯 QUERIES QUE ENTIENDE EL AI

### Simples (tinyllama - 0.5s):
- ✅ "habitaciones disponibles"
- ✅ "cuántas ocupadas"
- ✅ "dame las libres"
- ✅ "habitaciones booked"

### Complejas (qwen2.5 - 1.5s):
- ✅ "suites disponibles bajo $150"
- ✅ "habitaciones dobles con más de 3 personas"
- ✅ "cuál es el promedio de precio de ocupadas"
- ✅ "habitaciones premium sin reserva"

### Español e Inglés:
- ✅ "show me available rooms"
- ✅ "muéstrame habitaciones libres"
- ✅ "quantas habitações ocupadas"

---

## 🔄 AUTO-INVALIDACIÓN DE CACHE

### El cache se borra automáticamente cuando:

1. **Nueva reserva creada** → `CacheEventHooks::onBookingChange($hotelId)`
2. **Reserva cancelada** → `CacheEventHooks::onBookingChange($hotelId)`
3. **Habitación agregada/editada** → `CacheEventHooks::onRoomChange($hotelId)`
4. **Usuario hace logout** → Auto-limpieza

### Ejemplo de uso:

```php
// En tu código de booking:
$booking->create($data);
CacheEventHooks::onBookingChange($hotelId);  // ← Invalida cache
```

---

## 📈 MONITOREO

### Ver estadísticas de cache:

```php
$cache = CacheManager::getInstance();
$stats = $cache->getStats();

print_r($stats);
/*
Array (
    [total_caches] => 15
    [valid] => 12
    [expired] => 3
    [total_size_kb] => 45.2
)
*/
```

---

## 🚀 PRÓXIMOS PASOS

### Fase 1 (AHORA):
- [x] Cache inteligente
- [x] Queries optimizadas
- [x] AI routing
- [x] Contexto compacto

### Fase 2 (PRÓXIMA):
- [ ] Redis cache (para múltiples servidores)
- [ ] AI aprende de queries frecuentes
- [ ] Predicción de disponibilidad
- [ ] Analytics de uso AI

### Fase 3 (FUTURO):
- [ ] AI sugiere precios óptimos
- [ ] Detección de patrones de booking
- [ ] Alertas inteligentes
- [ ] Recomendaciones automáticas

---

## 💡 TIPS DE USO

### Para gerentes:
1. **Preguntas cortas** = Respuestas más rápidas
2. **Español o inglés** funcionan igual
3. **El AI recuerda tu hotel actual** (session)

### Para desarrolladores:
1. **Siempre invalidar cache** después de cambios
2. **Monitorear stats** para ver hit rate
3. **Ajustar TTL** según patrón de uso (300s default)

---

## 🔒 SEGURIDAD

- ✅ Solo usuarios autenticados
- ✅ Cache por sesión (no compartido entre usuarios)
- ✅ Validación de hotel_id
- ✅ Timeout en requests AI (no bloquea)
- ✅ Fallback sin AI si falla

---

## 📞 SOPORTE

Si el AI no responde:
1. Verifica que Ollama esté corriendo: `http://72.60.1.16:11434/api/tags`
2. Revisa logs del servidor
3. El sistema tiene **fallback automático** sin AI

---

**Creado por:** Sistema PMS Multi-Hotel  
**Versión:** 2.0 Optimizado  
**Fecha:** Noviembre 2025
