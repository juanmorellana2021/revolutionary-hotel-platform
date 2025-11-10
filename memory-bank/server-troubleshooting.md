# Server Troubleshooting Guide
## Metodología: Divide y Conquista (Paso a Paso)

### Principio Fundamental
**NUNCA intentes arreglar todo a la vez. Verifica cada componente INDIVIDUALMENTE.**

Como un rompecabezas: Si una pieza no encaja, revisa SOLO esa pieza primero, no todo el rompecabezas.

---

## Proceso de Troubleshooting

### 1. AISLAR el Problema
- ¿Qué componente específico está fallando?
- ¿Es el servidor? ¿La conexión? ¿El cliente? ¿La configuración?

### 2. PROBAR Cada Componente por Separado

#### Ejemplo: Blockchain JSON-RPC no responde

❌ **MAL Approach:**
```bash
# Intentar deployar contratos directamente
npx hardhat run deploy.js --network ainichain
# ERROR: No funciona, pero ¿por qué?
```

✅ **BUEN Approach (paso a paso):**

**Paso 1: ¿El servidor está corriendo?**
```bash
ps aux | grep polygon-edge
# ✅ o ❌ → Si no está, arrancarlo primero
```

**Paso 2: ¿El puerto está abierto?**
```bash
netstat -tlnp | grep 8545
# ✅ tcp6 :::8545 LISTEN → Puerto abierto
# ❌ Nada → Servidor no inició correctamente
```

**Paso 3: ¿Responde a peticiones simples?**
```bash
# Test básico con curl
curl -X POST -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","method":"eth_blockNumber","params":[],"id":1}' \
  http://127.0.0.1:8545
```

**Paso 4: ¿Es problema de IPv4 vs IPv6?**
```javascript
// Test con script Node.js para IPv6
const options = { hostname: '::1', port: 8545 };
// Si funciona con ::1 pero no con 127.0.0.1 → Problema de IP version
```

**Paso 5: SOLO cuando TODO lo anterior funciona:**
```bash
# Ahora sí, intenta el deployment completo
npx hardhat run deploy.js
```

---

## Lecciones del Caso: Polygon Edge + Hardhat

### Problema Original
Hardhat no podía deployar contratos → timeout error

### Errores Cometidos
1. ❌ Intentar deployar sin verificar si JSON-RPC funcionaba
2. ❌ Asumir que "puerto abierto" = "servidor funcionando"
3. ❌ No probar con herramientas simples primero (curl, nc, node script)
4. ❌ No identificar IPv4 vs IPv6 issue

### Solución Correcta (Paso a Paso)
1. ✅ Matar procesos antiguos: `pkill polygon-edge`
2. ✅ Arrancar servidor en foreground para ver errores
3. ✅ Identificar error: `failed to parse addr 'http://0.0.0.0:8545'` (formato incorrecto)
4. ✅ Corregir: usar `0.0.0.0:8545` sin `http://`
5. ✅ Verificar puerto: `netstat -tlnp | grep 8545` → tcp6 (IPv6)
6. ✅ Test simple: script Node.js con `::1` en lugar de `127.0.0.1`
7. ✅ Actualizar config Hardhat: `url: "http://[::1]:8545"`
8. ✅ Deploy

---

## Comandos Útiles para Troubleshooting

### Verificar Procesos
```bash
ps aux | grep <nombre-proceso>
pgrep <nombre>
top -p <PID>
```

### Verificar Puertos
```bash
netstat -tlnp | grep <puerto>
lsof -i :<puerto>
ss -tlnp | grep <puerto>
```

### Test de Conectividad
```bash
# TCP básico
nc -zv localhost 8545
telnet localhost 8545

# HTTP
curl -v http://localhost:8545

# Con timeout
timeout 5 curl http://localhost:8545
```

### Logs en Tiempo Real
```bash
# Foreground (ver errores inmediatos)
./servidor

# Background con logs
./servidor > /var/log/server.log 2>&1 &
tail -f /var/log/server.log
```

---

## Red Flags (Señales de Problema)

🚩 **"Connection timeout"** → Servidor no está escuchando o firewall
🚩 **"Connection refused"** → Puerto cerrado o servidor apagado  
🚩 **"Socket hang up"** → Servidor cierra conexión (bug o crash)
🚩 **"Address already in use"** → Otro proceso usando el puerto
🚩 **"Permission denied"** → Falta permisos (usar sudo o cambiar puerto >1024)
🚩 **"No route to host"** → Problema de red/firewall

---

## Regla de Oro

> **"Si no puedes hacer un test simple con curl o nc, NO intentes usar una herramienta compleja como Hardhat/Web3/SDK"**

**Orden correcto:**
1. Servidor arranca sin errores
2. Puerto está abierto (netstat)
3. Test manual funciona (curl/nc/script simple)
4. Herramienta compleja funciona

**NO al revés.**

---

*Documentado: 2025-11-10*
*Caso: Polygon Edge JSON-RPC troubleshooting*
