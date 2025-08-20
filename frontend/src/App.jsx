import { useAppBridge } from '@shopify/app-bridge-react'
import { Page, Layout, Card, Button, Text, Banner, ProgressBar, Spinner } from '@shopify/polaris'
import { useState } from 'react'

function App() {
  const shopify = useAppBridge()
  const [isImportingProducts, setIsImportingProducts] = useState(false)
  const [isImportingOrders, setIsImportingOrders] = useState(false)
  const [importStats, setImportStats] = useState(null)
  const [error, setError] = useState(null)

  // Lấy shop domain từ Shopify context
  const shopDomain = shopify?.config?.shop || 'unknown-shop'

  async function syncProducts() {
    setIsImportingProducts(true)
    setError(null)
    setImportStats(null)

    try {
      const response = await fetch('/api/import/products', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Shopify-Shop-Domain': shopDomain,
          'Authorization': `Bearer ${shopify?.idToken || ''}`
        },
        body: JSON.stringify({
          shop: shopDomain,
          clear: false
        })
      })

      const result = await response.json()

      if (result.success) {
        setImportStats(result.data)
        shopify.toast.show(`✅ Đồng bộ thành công ${result.data.total_products} products!`)
      } else {
        throw new Error(result.message || 'Import failed')
      }
    } catch (err) {
      setError(`Lỗi đồng bộ products: ${err.message}`)
      shopify.toast.show('❌ Đồng bộ products thất bại', { isError: true })
    } finally {
      setIsImportingProducts(false)
    }
  }

  async function syncOrders() {
    setIsImportingOrders(true)
    setError(null)
    setImportStats(null)

    try {
      const response = await fetch('/api/import/draft-orders', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Shopify-Shop-Domain': shopDomain,
          'Authorization': `Bearer ${shopify?.idToken || ''}`
        },
        body: JSON.stringify({
          shop: shopDomain,
          clear: false
        })
      })

      const result = await response.json()

      if (result.success) {
        setImportStats(result.data)
        shopify.toast.show(`✅ Đồng bộ thành công ${result.data.total_draft_orders} draft orders!`)
      } else {
        throw new Error(result.message || 'Import failed')
      }
    } catch (err) {
      setError(`Lỗi đồng bộ orders: ${err.message}`)
      shopify.toast.show('❌ Đồng bộ orders thất bại', { isError: true })
    } finally {
      setIsImportingOrders(false)
    }
  }

  async function syncAll() {
    setIsImportingProducts(true)
    setIsImportingOrders(true)
    setError(null)
    setImportStats(null)

    try {
      const response = await fetch('/api/import/all', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Shopify-Shop-Domain': shopDomain,
          'Authorization': `Bearer ${shopify?.idToken || ''}`
        },
        body: JSON.stringify({
          shop: shopDomain,
          clear: false
        })
      })

      const result = await response.json()

      if (result.success) {
        setImportStats(result.data)
        const totalProducts = result.data.products?.total_products || 0
        const totalOrders = result.data.draft_orders?.total_draft_orders || 0
        shopify.toast.show(`✅ Đồng bộ thành công ${totalProducts} products và ${totalOrders} draft orders!`)
      } else {
        throw new Error(result.message || 'Import failed')
      }
    } catch (err) {
      setError(`Lỗi đồng bộ dữ liệu: ${err.message}`)
      shopify.toast.show('❌ Đồng bộ dữ liệu thất bại', { isError: true })
    } finally {
      setIsImportingProducts(false)
      setIsImportingOrders(false)
    }
  }

  return (
    <Page title="Shopify Data Sync">
      <Layout>
        <Layout.Section>
          <Card sectioned>
            <Text as="h2" variant="headingMd">
              Đồng bộ dữ liệu Shopify 🔄
            </Text>
            <Text as="p" color="subdued">
              Đồng bộ products và draft orders từ Shopify về database của bạn
            </Text>
            <br />

            <div style={{
              display: 'flex',
              gap: '1rem',
              flexWrap: 'wrap',
              alignItems: 'center'
            }}>
              <Button
                primary
                onClick={syncProducts}
                loading={isImportingProducts}
                disabled={isImportingOrders}
              >
                {isImportingProducts ? 'Đang đồng bộ...' : '📦 Đồng bộ Products'}
              </Button>

              <Button
                onClick={syncOrders}
                loading={isImportingOrders}
                disabled={isImportingProducts}
              >
                {isImportingOrders ? 'Đang đồng bộ...' : '📋 Đồng bộ Draft Orders'}
              </Button>

              <Button
                onClick={syncAll}
                loading={isImportingProducts || isImportingOrders}
                tone="success"
              >
                {(isImportingProducts || isImportingOrders) ? 'Đang đồng bộ...' : '🚀 Đồng bộ Tất cả'}
              </Button>
            </div>

            {(isImportingProducts || isImportingOrders) && (
              <div style={{ marginTop: '1rem' }}>
                <div style={{
                  display: 'flex',
                  alignItems: 'center',
                  gap: '0.5rem',
                  marginBottom: '1rem'
                }}>
                  <Spinner size="small" />
                  <Text as="p">
                    {isImportingProducts && isImportingOrders
                      ? 'Đang đồng bộ tất cả dữ liệu...'
                      : isImportingProducts
                        ? 'Đang đồng bộ products...'
                        : 'Đang đồng bộ draft orders...'}
                  </Text>
                </div>
                <ProgressBar progress={50} />
              </div>
            )}
          </Card>
        </Layout.Section>

        {error && (
          <Layout.Section>
            <Banner status="critical" title="Lỗi đồng bộ">
              <Text as="p">{error}</Text>
            </Banner>
          </Layout.Section>
        )}

        {importStats && (
          <Layout.Section>
            <Card sectioned>
              <Text as="h3" variant="headingMd">
                📊 Kết quả đồng bộ
              </Text>
              <br />

              {importStats.products && (
                <div style={{ marginBottom: '1rem' }}>
                  <Text as="h4" variant="headingSm">Products:</Text>
                  <ul style={{ marginLeft: '1rem', marginTop: '0.5rem' }}>
                    <li>✅ {importStats.products.total_products} products</li>
                    <li>🔧 {importStats.products.total_variants} variants</li>
                    <li>🖼️ {importStats.products.total_images} images</li>
                  </ul>
                </div>
              )}

              {importStats.draft_orders && (
                <div style={{ marginBottom: '1rem' }}>
                  <Text as="h4" variant="headingSm">Draft Orders:</Text>
                  <ul style={{ marginLeft: '1rem', marginTop: '0.5rem' }}>
                    <li>📋 {importStats.draft_orders.total_draft_orders} draft orders</li>
                    <li>📝 {importStats.draft_orders.total_line_items} line items</li>
                  </ul>
                </div>
              )}

              {importStats.total_products && (
                <div>
                  <Text as="h4" variant="headingSm">Tổng hợp:</Text>
                  <ul style={{ marginLeft: '1rem', marginTop: '0.5rem' }}>
                    <li>📦 {importStats.total_products} products</li>
                    <li>🔧 {importStats.total_variants} variants</li>
                    <li>🖼️ {importStats.total_images} images</li>
                  </ul>
                </div>
              )}

              {importStats.total_draft_orders && (
                <div>
                  <ul style={{ marginLeft: '1rem', marginTop: '0.5rem' }}>
                    <li>📋 {importStats.total_draft_orders} draft orders</li>
                    <li>📝 {importStats.total_line_items} line items</li>
                  </ul>
                </div>
              )}
            </Card>
          </Layout.Section>
        )}

        <Layout.Section>
          <Card sectioned>
            <Text as="h3" variant="headingMd">
              💡 Hướng dẫn sử dụng
            </Text>
            <br />
            <ul style={{ marginLeft: '1rem' }}>
              <li><strong>Đồng bộ Products:</strong> Import tất cả products, variants và images từ Shopify</li>
              <li><strong>Đồng bộ Draft Orders:</strong> Import tất cả draft orders và line items từ Shopify</li>
              <li><strong>Đồng bộ Tất cả:</strong> Import cả products và draft orders cùng lúc</li>
            </ul>
            <br />
            <Text as="p" color="subdued">
              Shop hiện tại: <strong>{shopDomain}</strong>
            </Text>
          </Card>
        </Layout.Section>
      </Layout>
    </Page>
  )
}

export default App
