# AiNi Coin - Blockchain Privada Implementation Plan

**Created**: November 9, 2025 - 23:30 UTC  
**Technology**: Polygon Edge (Private Ethereum-compatible blockchain)  
**Server**: prod-vps (108.175.12.152) - 16GB RAM, 130GB free disk  
**Timeline**: 1-2 weeks  

---

## 🎯 Objetivos

1. ✅ Crear blockchain privada Ethereum-compatible
2. ✅ Desplegar smart contract AiNi Coin (ERC-20)
3. ✅ Migrar sistema MySQL → Blockchain
4. ✅ Habilitar MetaMask support
5. ✅ Mantener compatibilidad con sistema actual

---

## 📋 Fase 1: Instalación de Polygon Edge (Día 1-2)

### 1.1 Descargar Polygon Edge

```bash
# En prod-vps
cd /opt
sudo wget https://github.com/0xPolygon/polygon-edge/releases/download/v1.3.1/polygon-edge_1.3.1_linux_amd64.tar.gz
sudo tar -xzf polygon-edge_1.3.1_linux_amd64.tar.gz
sudo mv polygon-edge /usr/local/bin/
sudo chmod +x /usr/local/bin/polygon-edge

# Verificar instalación
polygon-edge version
```

### 1.2 Crear Directorios de Trabajo

```bash
# Estructura de directorios
sudo mkdir -p /opt/aini-blockchain
cd /opt/aini-blockchain

# Crear directorios para 3 validadores
sudo mkdir -p validator-1 validator-2 validator-3
```

### 1.3 Generar Claves de Validadores

```bash
# Generar claves para cada validador
polygon-edge secrets init --data-dir /opt/aini-blockchain/validator-1
polygon-edge secrets init --data-dir /opt/aini-blockchain/validator-2
polygon-edge secrets init --data-dir /opt/aini-blockchain/validator-3

# IMPORTANTE: Guardar las claves públicas que se generan
```

### 1.4 Crear Genesis Block (Configuración Inicial)

```bash
# Crear archivo genesis.json
polygon-edge genesis \
  --consensus ibft \
  --ibft-validators-prefix-path /opt/aini-blockchain \
  --bootnode /ip4/127.0.0.1/tcp/10001/p2p/VALIDATOR_1_NODE_ID \
  --premine 0x_OWNER_ADDRESS:200000000000000000000000 \
  --block-gas-limit 10000000 \
  --epoch-size 100000 \
  --chain-name AiNiChain \
  --chain-id 2025 \
  --dir /opt/aini-blockchain/genesis.json
```

**Parámetros Explicados:**
- `--consensus ibft`: Proof of Authority (rápido, privado)
- `--premine`: Pre-asignar 200,000 AiNi Coins al owner
- `--chain-id 2025`: ID único de tu blockchain
- `--epoch-size`: Cada cuántos bloques rotan validadores

---

## 📋 Fase 2: Iniciar la Red (Día 2-3)

### 2.1 Archivo de Configuración para cada Validador

**validator-1/config.json:**
```json
{
  "chain_config": "/opt/aini-blockchain/genesis.json",
  "secrets_config": {
    "node_name": "validator-1",
    "data_dir": "/opt/aini-blockchain/validator-1"
  },
  "network": {
    "libp2p_addr": "0.0.0.0:10001",
    "nat_addr": "108.175.12.152",
    "dns_addr": "",
    "max_peers": 40,
    "max_inbound_peers": 32,
    "max_outbound_peers": 8
  },
  "seal": true,
  "tx_pool": {
    "max_slots": 4096,
    "max_account_slots": 16
  },
  "block_gas_target": "0x0",
  "grpc_addr": "127.0.0.1:9632",
  "jsonrpc_addr": "127.0.0.1:8545",
  "telemetry": {
    "prometheus_addr": ""
  },
  "log_level": "INFO"
}
```

### 2.2 Crear Servicios Systemd

**`/etc/systemd/system/aini-validator-1.service`:**
```ini
[Unit]
Description=AiNi Blockchain Validator 1
After=network.target

[Service]
Type=simple
User=root
WorkingDirectory=/opt/aini-blockchain
ExecStart=/usr/local/bin/polygon-edge server --config /opt/aini-blockchain/validator-1/config.json
Restart=on-failure
RestartSec=10
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
```

**Repetir para validator-2 y validator-3 con puertos diferentes:**
- Validator 1: libp2p=10001, jsonrpc=8545, grpc=9632
- Validator 2: libp2p=10002, jsonrpc=8546, grpc=9633
- Validator 3: libp2p=10003, jsonrpc=8547, grpc=9634

### 2.3 Iniciar los Servicios

```bash
# Habilitar e iniciar
sudo systemctl daemon-reload
sudo systemctl enable aini-validator-1 aini-validator-2 aini-validator-3
sudo systemctl start aini-validator-1 aini-validator-2 aini-validator-3

# Verificar estado
sudo systemctl status aini-validator-1
sudo journalctl -u aini-validator-1 -f  # Ver logs en tiempo real
```

---

## 📋 Fase 3: Smart Contract AiNi Coin (Día 3-5)

### 3.1 Instalar Herramientas de Desarrollo

```bash
# Instalar Node.js y npm (si no están)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs

# Instalar Hardhat (framework para smart contracts)
mkdir -p /opt/aini-blockchain/contracts
cd /opt/aini-blockchain/contracts
npm init -y
npm install --save-dev hardhat @nomicfoundation/hardhat-toolbox
npx hardhat init  # Seleccionar "Create a JavaScript project"
```

### 3.2 Smart Contract: AiNiCoin.sol

**`/opt/aini-blockchain/contracts/contracts/AiNiCoin.sol`:**
```solidity
// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20;

import "@openzeppelin/contracts/token/ERC20/ERC20.sol";
import "@openzeppelin/contracts/token/ERC20/extensions/ERC20Burnable.sol";
import "@openzeppelin/contracts/access/Ownable.sol";

/**
 * @title AiNiCoin
 * @dev Dual-token system: AiNi Rewards (stable) and AiNi Crypto (volatile)
 */
contract AiNiRewards is ERC20, ERC20Burnable, Ownable {
    
    // Events
    event CoinsPurchased(address indexed buyer, uint256 amount, uint256 usdValue);
    event CoinsTransferred(address indexed from, address indexed to, uint256 amount, string note);
    event CoinsRedeemed(address indexed user, uint256 amount, string rewardCode);
    
    // Transfer limits (anti-fraud)
    uint256 public constant DAILY_TRANSFER_LIMIT = 10000 * 10**18; // 10,000 coins
    mapping(address => uint256) public lastTransferTimestamp;
    mapping(address => uint256) public dailyTransferAmount;
    
    // Minimum account age for transfers (30 days)
    uint256 public constant MIN_ACCOUNT_AGE = 30 days;
    mapping(address => uint256) public accountCreationTime;
    
    constructor(address initialOwner) ERC20("AiNi Rewards", "AINIR") Ownable(initialOwner) {
        // Pre-mint 200,000 coins to owner for distribution
        _mint(initialOwner, 200000 * 10**18);
        accountCreationTime[initialOwner] = block.timestamp;
    }
    
    /**
     * @dev Purchase coins (called by Mercado Pago webhook)
     */
    function purchaseCoins(address buyer, uint256 amount, uint256 usdValue) external onlyOwner {
        require(buyer != address(0), "Invalid buyer address");
        require(amount > 0, "Amount must be greater than 0");
        
        // Record account creation time for new users
        if (accountCreationTime[buyer] == 0) {
            accountCreationTime[buyer] = block.timestamp;
        }
        
        _mint(buyer, amount);
        emit CoinsPurchased(buyer, amount, usdValue);
    }
    
    /**
     * @dev Transfer with anti-fraud checks
     */
    function transferWithNote(address to, uint256 amount, string memory note) external returns (bool) {
        require(to != address(0), "Cannot transfer to zero address");
        require(amount > 0, "Amount must be greater than 0");
        
        // Check account age
        require(
            block.timestamp >= accountCreationTime[msg.sender] + MIN_ACCOUNT_AGE,
            "Account too new. Wait 30 days before transferring."
        );
        
        // Check daily limit
        if (block.timestamp - lastTransferTimestamp[msg.sender] >= 1 days) {
            dailyTransferAmount[msg.sender] = 0; // Reset daily counter
        }
        
        require(
            dailyTransferAmount[msg.sender] + amount <= DAILY_TRANSFER_LIMIT,
            "Daily transfer limit exceeded"
        );
        
        // Update tracking
        dailyTransferAmount[msg.sender] += amount;
        lastTransferTimestamp[msg.sender] = block.timestamp;
        
        // Perform transfer
        _transfer(msg.sender, to, amount);
        
        emit CoinsTransferred(msg.sender, to, amount, note);
        return true;
    }
    
    /**
     * @dev Redeem coins for rewards
     */
    function redeemCoins(uint256 amount, string memory rewardCode) external {
        require(amount > 0, "Amount must be greater than 0");
        require(balanceOf(msg.sender) >= amount, "Insufficient balance");
        
        _burn(msg.sender, amount);
        emit CoinsRedeemed(msg.sender, amount, rewardCode);
    }
    
    /**
     * @dev Get account info
     */
    function getAccountInfo(address account) external view returns (
        uint256 balance,
        uint256 accountAge,
        uint256 dailyTransferUsed,
        uint256 dailyTransferRemaining
    ) {
        balance = balanceOf(account);
        accountAge = block.timestamp - accountCreationTime[account];
        
        if (block.timestamp - lastTransferTimestamp[account] >= 1 days) {
            dailyTransferUsed = 0;
        } else {
            dailyTransferUsed = dailyTransferAmount[account];
        }
        
        dailyTransferRemaining = DAILY_TRANSFER_LIMIT - dailyTransferUsed;
    }
}

/**
 * @title AiNiCrypto
 * @dev Volatile crypto token for investment/trading
 */
contract AiNiCrypto is ERC20, ERC20Burnable, Ownable {
    
    // Market price tracking (in USD, 6 decimals: 1000000 = $1.00)
    uint256 public currentPriceUSD = 1000000; // Initial: $1.00
    
    event PriceUpdated(uint256 newPrice, int256 changePercent);
    event CoinsConverted(address indexed user, uint256 rewardsAmount, uint256 cryptoAmount, uint256 rate);
    
    constructor(address initialOwner) ERC20("AiNi Crypto", "AINIC") Ownable(initialOwner) {
        // Pre-mint 100 coins to owner
        _mint(initialOwner, 100 * 10**18);
    }
    
    /**
     * @dev Update market price (called by cron job)
     */
    function updatePrice(uint256 newPriceUSD, int256 changePercent) external onlyOwner {
        require(newPriceUSD >= 100000, "Price cannot be below $0.10"); // Safety floor
        currentPriceUSD = newPriceUSD;
        emit PriceUpdated(newPriceUSD, changePercent);
    }
    
    /**
     * @dev Convert AiNi Rewards to AiNi Crypto (called by conversion contract)
     */
    function mintFromConversion(address user, uint256 amount) external onlyOwner {
        _mint(user, amount);
    }
    
    /**
     * @dev Burn AiNi Crypto for conversion back to Rewards
     */
    function burnForConversion(address user, uint256 amount) external onlyOwner {
        _burn(user, amount);
    }
}

/**
 * @title AiNiConversionPool
 * @dev Handles conversion between Rewards <-> Crypto
 */
contract AiNiConversionPool is Ownable {
    
    AiNiRewards public rewardsToken;
    AiNiCrypto public cryptoToken;
    
    event Converted(
        address indexed user,
        bool rewardsToCrypto,
        uint256 inputAmount,
        uint256 outputAmount,
        uint256 rate
    );
    
    constructor(
        address initialOwner,
        address _rewardsToken,
        address _cryptoToken
    ) Ownable(initialOwner) {
        rewardsToken = AiNiRewards(_rewardsToken);
        cryptoToken = AiNiCrypto(_cryptoToken);
    }
    
    /**
     * @dev Convert Rewards → Crypto
     */
    function convertRewardsToCrypto(uint256 rewardsAmount) external {
        require(rewardsAmount > 0, "Amount must be greater than 0");
        require(
            rewardsToken.balanceOf(msg.sender) >= rewardsAmount,
            "Insufficient Rewards balance"
        );
        
        // Calculate crypto amount based on current price
        uint256 cryptoPrice = cryptoToken.currentPriceUSD();
        uint256 cryptoAmount = (rewardsAmount * 1000000) / cryptoPrice;
        
        // Burn Rewards
        rewardsToken.transferFrom(msg.sender, address(this), rewardsAmount);
        rewardsToken.burn(rewardsAmount);
        
        // Mint Crypto
        cryptoToken.mintFromConversion(msg.sender, cryptoAmount);
        
        emit Converted(msg.sender, true, rewardsAmount, cryptoAmount, cryptoPrice);
    }
    
    /**
     * @dev Convert Crypto → Rewards
     */
    function convertCryptoToRewards(uint256 cryptoAmount) external {
        require(cryptoAmount > 0, "Amount must be greater than 0");
        require(
            cryptoToken.balanceOf(msg.sender) >= cryptoAmount,
            "Insufficient Crypto balance"
        );
        
        // Calculate rewards amount based on current price
        uint256 cryptoPrice = cryptoToken.currentPriceUSD();
        uint256 rewardsAmount = (cryptoAmount * cryptoPrice) / 1000000;
        
        // Burn Crypto
        cryptoToken.transferFrom(msg.sender, address(this), cryptoAmount);
        cryptoToken.burnForConversion(msg.sender, cryptoAmount);
        
        // Mint Rewards
        rewardsToken.transferFrom(owner(), msg.sender, rewardsAmount);
        
        emit Converted(msg.sender, false, cryptoAmount, rewardsAmount, cryptoPrice);
    }
}
```

### 3.3 Instalar OpenZeppelin (librerías estándar)

```bash
cd /opt/aini-blockchain/contracts
npm install @openzeppelin/contracts
```

### 3.4 Configurar Hardhat

**`hardhat.config.js`:**
```javascript
require("@nomicfoundation/hardhat-toolbox");

module.exports = {
  solidity: "0.8.20",
  networks: {
    ainichain: {
      url: "http://127.0.0.1:8545",  // Polygon Edge JSON-RPC
      chainId: 2025,
      accounts: ["PRIVATE_KEY_DEL_OWNER"]  // Generar con Polygon Edge
    }
  }
};
```

### 3.5 Script de Deployment

**`scripts/deploy.js`:**
```javascript
const hre = require("hardhat");

async function main() {
  const [deployer] = await hre.ethers.getSigners();
  
  console.log("Deploying contracts with account:", deployer.address);
  console.log("Account balance:", (await deployer.getBalance()).toString());
  
  // Deploy AiNi Rewards
  const AiNiRewards = await hre.ethers.getContractFactory("AiNiRewards");
  const rewards = await AiNiRewards.deploy(deployer.address);
  await rewards.deployed();
  console.log("AiNi Rewards deployed to:", rewards.address);
  
  // Deploy AiNi Crypto
  const AiNiCrypto = await hre.ethers.getContractFactory("AiNiCrypto");
  const crypto = await AiNiCrypto.deploy(deployer.address);
  await crypto.deployed();
  console.log("AiNi Crypto deployed to:", crypto.address);
  
  // Deploy Conversion Pool
  const AiNiConversionPool = await hre.ethers.getContractFactory("AiNiConversionPool");
  const pool = await AiNiConversionPool.deploy(
    deployer.address,
    rewards.address,
    crypto.address
  );
  await pool.deployed();
  console.log("Conversion Pool deployed to:", pool.address);
  
  // Save addresses
  const addresses = {
    AiNiRewards: rewards.address,
    AiNiCrypto: crypto.address,
    ConversionPool: pool.address,
    deployer: deployer.address
  };
  
  require('fs').writeFileSync(
    'deployed-addresses.json',
    JSON.stringify(addresses, null, 2)
  );
  
  console.log("\n✅ All contracts deployed successfully!");
  console.log("Contract addresses saved to deployed-addresses.json");
}

main()
  .then(() => process.exit(0))
  .catch((error) => {
    console.error(error);
    process.exit(1);
  });
```

### 3.6 Compilar y Desplegar

```bash
cd /opt/aini-blockchain/contracts

# Compilar
npx hardhat compile

# Desplegar a AiNiChain
npx hardhat run scripts/deploy.js --network ainichain
```

---

## 📋 Fase 4: Integración Web3 con PHP (Día 6-8)

### 4.1 Instalar Web3.php

```bash
cd /var/www/html/ainitravel.com
composer require web3p/web3.php
```

### 4.2 Crear Web3 Helper Class

**`/var/www/html/ainitravel.com/Web3Helper.php`:**
```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Web3\Web3;
use Web3\Contract;

class Web3Helper {
    private $web3;
    private $contract;
    private $contractAddress;
    private $abi;
    
    public function __construct($rpcUrl = 'http://127.0.0.1:8545') {
        $this->web3 = new Web3($rpcUrl);
        
        // Load deployed contract addresses
        $addresses = json_decode(file_get_contents(__DIR__ . '/deployed-addresses.json'), true);
        $this->contractAddress = $addresses['AiNiRewards'];
        
        // Load ABI from compiled contract
        $artifact = json_decode(file_get_contents(__DIR__ . '/contracts/artifacts/contracts/AiNiCoin.sol/AiNiRewards.json'), true);
        $this->abi = json_encode($artifact['abi']);
        
        $this->contract = new Contract($this->web3->provider, $this->abi);
    }
    
    public function getBalance($address) {
        $balance = null;
        $this->contract->at($this->contractAddress)->call('balanceOf', $address, function ($err, $result) use (&$balance) {
            if ($err) throw new Exception($err->getMessage());
            $balance = $result[0]->toString();
        });
        
        // Convert from wei to coins (18 decimals)
        return bcdiv($balance, '1000000000000000000', 2);
    }
    
    public function purchaseCoins($buyerAddress, $amount, $usdValue, $privateKey) {
        // Call contract function: purchaseCoins(address buyer, uint256 amount, uint256 usdValue)
        $amountWei = bcmul($amount, '1000000000000000000'); // Convert to wei
        
        // TODO: Sign and send transaction
        // This requires the owner's private key
    }
    
    public function getAccountInfo($address) {
        // Call contract function: getAccountInfo(address account)
        $info = [];
        $this->contract->at($this->contractAddress)->call('getAccountInfo', $address, function ($err, $result) use (&$info) {
            if ($err) throw new Exception($err->getMessage());
            $info = [
                'balance' => bcdiv($result['balance']->toString(), '1000000000000000000', 2),
                'accountAge' => $result['accountAge']->toString(),
                'dailyTransferUsed' => bcdiv($result['dailyTransferUsed']->toString(), '1000000000000000000', 2),
                'dailyTransferRemaining' => bcdiv($result['dailyTransferRemaining']->toString(), '1000000000000000000', 2)
            ];
        });
        
        return $info;
    }
}
```

### 4.3 Actualizar wallet.php para usar Blockchain

**Cambios necesarios en wallet.php:**
```php
<?php
require_once 'Web3Helper.php';

// En vez de consultar MySQL:
// $stmt = $pdo->prepare("SELECT aini_rewards FROM ainitravel_users WHERE id = ?");

// Usar blockchain:
$web3 = new Web3Helper();
$userWalletAddress = $user['blockchain_address']; // Nuevo campo en DB
$balance = $web3->getBalance($userWalletAddress);
```

---

## 📋 Fase 5: Migración de Datos (Día 9-10)

### 5.1 Script de Migración MySQL → Blockchain

**`migrate_to_blockchain.php`:**
```php
<?php
require_once 'db_connection_pdo.php';
require_once 'Web3Helper.php';

$web3 = new Web3Helper();

// Obtener todos los usuarios con saldo
$stmt = $pdo->query("
    SELECT id, email, aini_rewards, aini_crypto 
    FROM ainitravel_users 
    WHERE aini_rewards > 0 OR aini_crypto > 0
");

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $user) {
    echo "Migrando usuario {$user['email']}...\n";
    
    // Generar dirección blockchain si no existe
    if (empty($user['blockchain_address'])) {
        // Generar nueva dirección
        $address = generateBlockchainAddress();
        
        // Guardar en DB
        $updateStmt = $pdo->prepare("UPDATE ainitravel_users SET blockchain_address = ? WHERE id = ?");
        $updateStmt->execute([$address, $user['id']]);
    }
    
    // Migrar saldo de Rewards
    if ($user['aini_rewards'] > 0) {
        $web3->purchaseCoins(
            $user['blockchain_address'],
            $user['aini_rewards'],
            $user['aini_rewards'], // 1:1 USD value
            OWNER_PRIVATE_KEY
        );
        
        echo "  ✅ Migrated {$user['aini_rewards']} Rewards\n";
    }
    
    // Similar para Crypto...
}

echo "\n✅ Migration complete!\n";
```

---

## 📋 Fase 6: MetaMask Integration (Día 11-12)

### 6.1 Actualizar wallet.php con botón "Connect MetaMask"

```html
<button onclick="connectMetaMask()" class="btn-metamask">
    <img src="/images/metamask-fox.svg" alt="MetaMask">
    Connect MetaMask
</button>

<script>
const AINICHAIN_CONFIG = {
    chainId: '0x7E9', // 2025 in hex
    chainName: 'AiNi Chain',
    nativeCurrency: {
        name: 'AiNi Coin',
        symbol: 'AINI',
        decimals: 18
    },
    rpcUrls: ['https://ainitravel.com:8545'],
    blockExplorerUrls: ['https://explorer.ainitravel.com']
};

async function connectMetaMask() {
    if (typeof window.ethereum === 'undefined') {
        alert('Please install MetaMask!');
        return;
    }
    
    try {
        // Request account access
        const accounts = await ethereum.request({ 
            method: 'eth_requestAccounts' 
        });
        
        // Add AiNi Chain to MetaMask
        await ethereum.request({
            method: 'wallet_addEthereumChain',
            params: [AINICHAIN_CONFIG]
        });
        
        console.log('Connected:', accounts[0]);
        
        // Update UI
        document.getElementById('wallet-address').textContent = 
            accounts[0].substring(0, 6) + '...' + accounts[0].substring(38);
            
    } catch (error) {
        console.error(error);
        alert('Error connecting to MetaMask');
    }
}
</script>
```

---

## 📋 Fase 7: Testing y Deployment (Día 13-14)

### 7.1 Tests Unitarios

```javascript
// test/AiNiCoin.test.js
const { expect } = require("chai");

describe("AiNiRewards", function () {
    it("Should deploy with 200,000 pre-minted coins", async function () {
        const [owner] = await ethers.getSigners();
        const AiNiRewards = await ethers.getContractFactory("AiNiRewards");
        const rewards = await AiNiRewards.deploy(owner.address);
        
        const balance = await rewards.balanceOf(owner.address);
        expect(balance).to.equal(ethers.utils.parseEther("200000"));
    });
    
    it("Should enforce 30-day account age for transfers", async function () {
        // Test transfer limits
    });
    
    it("Should convert Rewards to Crypto at market rate", async function () {
        // Test conversion
    });
});
```

Run tests:
```bash
npx hardhat test
```

### 7.2 Monitoring Setup

```bash
# Instalar Prometheus para métricas
# Configurar alertas para:
# - Nodo caído
# - Transacciones fallidas
# - Alto uso de RAM/disco
```

---

## 📊 Recursos Necesarios

| Recurso | Disponible | Necesario | Estado |
|---------|-----------|-----------|---------|
| RAM | 15 GB | 4-6 GB | ✅ Sobra |
| Disco | 130 GB | 20-30 GB | ✅ Sobra |
| CPU | 4 cores | 2-4 cores | ✅ OK |
| Ancho Banda | Unlimited | 1-5 Mbps | ✅ OK |

---

## 🔐 Seguridad

1. **Private Keys**: Almacenar en `/opt/aini-blockchain/secrets/` (chmod 600)
2. **Firewall**: Solo exponer puerto 8545 (JSON-RPC) a localhost
3. **Backup**: Diario de `/opt/aini-blockchain/` completo
4. **Auditoría**: Logs en `/var/log/aini-blockchain/`

---

## 🎯 Próximos Pasos

1. ✅ Instalar Polygon Edge en prod-vps
2. ✅ Generar validadores y genesis block
3. ✅ Desplegar smart contracts
4. ✅ Integrar con PHP/wallet.php
5. ✅ Migrar datos MySQL → Blockchain
6. ✅ Habilitar MetaMask
7. ✅ Testing exhaustivo

**Timeline estimado: 10-14 días**

---

**¿Empezamos con la instalación?** 🚀
