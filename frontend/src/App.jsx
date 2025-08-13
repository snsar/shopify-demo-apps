import { useState } from 'react'
import { useAppBridge } from '@shopify/app-bridge-react'
import { 
  Page, 
  Layout, 
  Card, 
  Button, 
  Toast, 
  Frame,
  Modal,
  TitleBar,
  Text,
  ButtonGroup,
  Banner
} from '@shopify/polaris'
import './App.css'

function App() {
  const [count, setCount] = useState(0)
  const [showToast, setShowToast] = useState(false)
  const [modalActive, setModalActive] = useState(false)
  const shopify = useAppBridge()

  const handleToastShow = () => {
    if (shopify) {
      // Sử dụng App Bridge toast
      shopify.toast.show('Đây là toast từ App Bridge!')
    } else {
      // Fallback cho Polaris toast
      setShowToast(true)
    }
  }

  const handleModalOpen = () => {
    setModalActive(true)
  }

  const handleModalClose = () => {
    setModalActive(false)
  }

  const handlePrimaryAction = () => {
    if (shopify) {
      shopify.toast.show('Hành động chính đã được thực hiện!')
    }
    setModalActive(false)
  }

  const toastMarkup = showToast ? (
    <Toast content="Polaris Toast hiển thị!" onDismiss={() => setShowToast(false)} />
  ) : null

  return (
    <Frame>
      <Page
        title="Shopify App với App Bridge React"
        subtitle="Ứng dụng demo tích hợp App Bridge và Polaris"
      >
        <Layout>
          <Layout.Section>
            <Banner
              title="App Bridge React đã được tích hợp"
              status="success"
            >
              <p>Ứng dụng của bạn đã sẵn sàng sử dụng các tính năng của Shopify App Bridge.</p>
            </Banner>
          </Layout.Section>

          <Layout.Section>
            <Card title="Counter Demo" sectioned>
              <div style={{ textAlign: 'center', marginBottom: '1rem' }}>
                <Text variant="headingLg" as="h2">
                  Count: {count}
                </Text>
              </div>
              
              <ButtonGroup>
                <Button 
                  primary 
                  onClick={() => setCount((count) => count + 1)}
                >
                  Tăng Counter
                </Button>
                <Button onClick={() => setCount(0)}>
                  Reset
                </Button>
              </ButtonGroup>
            </Card>
          </Layout.Section>

          <Layout.Section>
            <Card title="App Bridge Features" sectioned>
              <div style={{ display: 'flex', gap: '1rem', flexWrap: 'wrap' }}>
                <Button onClick={handleToastShow}>
                  Hiển thị Toast
                </Button>
                <Button onClick={handleModalOpen}>
                  Mở Modal
                </Button>
              </div>
            </Card>
          </Layout.Section>
        </Layout>

        {/* Modal using App Bridge */}
        <Modal
          open={modalActive}
          onClose={handleModalClose}
          title="App Bridge Modal"
          primaryAction={{
            content: 'Xác nhận',
            onAction: handlePrimaryAction,
          }}
          secondaryActions={[
            {
              content: 'Hủy',
              onAction: handleModalClose,
            },
          ]}
        >
          <Modal.Section>
            <Text as="p">
              Đây là modal được tạo bằng Shopify App Bridge React. 
              Modal này tự động tích hợp với giao diện Shopify Admin.
            </Text>
          </Modal.Section>
        </Modal>

        {toastMarkup}
      </Page>
    </Frame>
  )
}

export default App
