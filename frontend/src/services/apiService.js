import axios from 'axios'

class ApiService {
  constructor(shopify) {
    this.shopify = shopify
    this.shopDomain = shopify?.config?.shop || 'unknown-shop'
    
    // Tạo axios instance với cấu hình mặc định
    this.api = axios.create({
      baseURL: '/api',
      headers: {
        'Content-Type': 'application/json',
      },
      timeout: 30000, // 30 seconds
    })

    // Request interceptor để tự động thêm headers
    this.api.interceptors.request.use(
      (config) => {
        config.headers['X-Shopify-Shop-Domain'] = this.shopDomain
        config.headers['Authorization'] = `Bearer ${this.shopify?.idToken || ''}`
        return config
      },
      (error) => {
        return Promise.reject(error)
      }
    )

    // Response interceptor để xử lý response chung
    this.api.interceptors.response.use(
      (response) => {
        return response.data
      },
      (error) => {
        // Xử lý lỗi chung
        const errorMessage = error.response?.data?.message || error.message || 'Đã xảy ra lỗi'
        return Promise.reject(new Error(errorMessage))
      }
    )
  }

  // Import products
  async importProducts(options = {}) {
    const { clear = false } = options
    
    try {
      const result = await this.api.post('/import/products', {
        shop: this.shopDomain,
        clear
      })

      if (result.success) {
        this.shopify?.toast?.show(`✅ Đồng bộ thành công ${result.data.total_products} products!`)
        return result.data
      } else {
        throw new Error(result.message || 'Import failed')
      }
    } catch (error) {
      this.shopify?.toast?.show('❌ Đồng bộ products thất bại', { isError: true })
      throw error
    }
  }

  // Import draft orders
  async importDraftOrders(options = {}) {
    const { clear = false } = options
    
    try {
      const result = await this.api.post('/import/draft-orders', {
        shop: this.shopDomain,
        clear
      })

      if (result.success) {
        this.shopify?.toast?.show(`✅ Đồng bộ thành công ${result.data.total_draft_orders} draft orders!`)
        return result.data
      } else {
        throw new Error(result.message || 'Import failed')
      }
    } catch (error) {
      this.shopify?.toast?.show('❌ Đồng bộ draft orders thất bại', { isError: true })
      throw error
    }
  }

  // Import orders
  async importOrders(options = {}) {
    const { clear = false } = options
    
    try {
      const result = await this.api.post('/import/orders', {
        shop: this.shopDomain,
        clear
      })

      if (result.success) {
        this.shopify?.toast?.show(`✅ Đồng bộ thành công ${result.data.total_orders} orders!`)
        return result.data
      } else {
        throw new Error(result.message || 'Import failed')
      }
    } catch (error) {
      this.shopify?.toast?.show('❌ Đồng bộ orders thất bại', { isError: true })
      throw error
    }
  }

  // Import all data
  async importAll(options = {}) {
    const { clear = false } = options
    
    try {
      const result = await this.api.post('/import/all', {
        shop: this.shopDomain,
        clear
      })

      if (result.success) {
        this.shopify?.toast?.show(`✅ Đồng bộ tất cả dữ liệu thành công!`)
        return result.data
      } else {
        throw new Error(result.message || 'Import all failed')
      }
    } catch (error) {
      this.shopify?.toast?.show('❌ Đồng bộ tất cả dữ liệu thất bại', { isError: true })
      throw error
    }
  }

  // Generic GET method
  async get(endpoint, params = {}) {
    try {
      return await this.api.get(endpoint, { params })
    } catch (error) {
      this.shopify?.toast?.show(`❌ Lỗi GET ${endpoint}`, { isError: true })
      throw error
    }
  }

  // Generic POST method
  async post(endpoint, data = {}) {
    try {
      return await this.api.post(endpoint, {
        shop: this.shopDomain,
        ...data
      })
    } catch (error) {
      this.shopify?.toast?.show(`❌ Lỗi POST ${endpoint}`, { isError: true })
      throw error
    }
  }

  // Generic PUT method
  async put(endpoint, data = {}) {
    try {
      return await this.api.put(endpoint, {
        shop: this.shopDomain,
        ...data
      })
    } catch (error) {
      this.shopify?.toast?.show(`❌ Lỗi PUT ${endpoint}`, { isError: true })
      throw error
    }
  }

  // Generic DELETE method
  async delete(endpoint, data = {}) {
    try {
      return await this.api.delete(endpoint, {
        data: {
          shop: this.shopDomain,
          ...data
        }
      })
    } catch (error) {
      this.shopify?.toast?.show(`❌ Lỗi DELETE ${endpoint}`, { isError: true })
      throw error
    }
  }

  // Metafield operations
  async createOrUpdateShopMetafield(metafieldData) {
    try {
      // Sử dụng method post() để tự động thêm shop parameter
      const result = await this.post('/shopify/metafield', metafieldData)
      
      if (result.success) {
        const operation = result.data.operation
        this.shopify?.toast?.show(`✅ Shop metafield ${operation === 'created' ? 'đã tạo' : 'đã cập nhật'} thành công!`)
        return result.data
      } else {
        throw new Error(result.message || 'Metafield operation failed')
      }
    } catch (error) {
      this.shopify?.toast?.show('❌ Lỗi xử lý metafield', { isError: true })
      throw error
    }
  }

  async getShopMetafields() {
    try {
      // Sử dụng method get() để tự động thêm shop parameter
      const result = await this.get('/shopify/metafields', { shop: this.shopDomain })
      
      if (result.success) {
        return result.data
      } else {
        throw new Error(result.message || 'Failed to get metafields')
      }
    } catch (error) {
      this.shopify?.toast?.show('❌ Lỗi lấy danh sách metafields', { isError: true })
      throw error
    }
  }

  // Quote Configuration methods (fast DB access)
  async saveQuoteConfiguration(config) {
    try {
      const result = await this.post('/quote-config/save', config)
      
      if (result.success) {
        this.shopify?.toast?.show('✅ Cấu hình đã được lưu thành công!')
        return result.data
      } else {
        throw new Error(result.message || 'Save configuration failed')
      }
    } catch (error) {
      this.shopify?.toast?.show('❌ Lỗi lưu cấu hình', { isError: true })
      throw error
    }
  }

  async getQuoteConfiguration() {
    try {
      const result = await this.get('/quote-config/', { shop: this.shopDomain })
      
      if (result.success) {
        return result.data
      } else {
        throw new Error(result.message || 'Failed to get configuration')
      }
    } catch (error) {
      console.error('Error getting quote configuration:', error)
      // Return default config if error
      return {
        displayRule: 'all',
        position: 'under-button',
        isActive: true,
        buttonLabel: 'Request for quote',
        alignment: 'center',
        fontSize: 15,
        cornerRadius: 15,
        textColor: { hue: 0, brightness: 1, saturation: 0 },
        buttonColor: { hue: 39, brightness: 1, saturation: 1 },
      }
    }
  }

  // Sync configuration to Shopify metafield
  async syncConfigToShopify() {
    try {
      const result = await this.post('/quote-config/sync-to-shopify')
      
      if (result.success) {
        this.shopify?.toast?.show('✅ Đã đồng bộ cấu hình lên Shopify!')
        return result
      } else {
        throw new Error(result.message || 'Sync failed')
      }
    } catch (error) {
      this.shopify?.toast?.show('❌ Lỗi đồng bộ lên Shopify', { isError: true })
      throw error
    }
  }

  // Import configuration from Shopify metafield
  async importConfigFromShopify() {
    try {
      const result = await this.post('/quote-config/import-from-shopify')
      
      if (result.success) {
        this.shopify?.toast?.show('✅ Đã import cấu hình từ Shopify!')
        return result.data
      } else {
        throw new Error(result.message || 'Import failed')
      }
    } catch (error) {
      this.shopify?.toast?.show('❌ Lỗi import từ Shopify', { isError: true })
      throw error
    }
  }
}

export default ApiService
