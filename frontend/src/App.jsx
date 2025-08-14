import { useAppBridge } from '@shopify/app-bridge-react'
import { Page, Layout, Card, Button, Text } from '@shopify/polaris'

function App() {
  const shopify = useAppBridge()

  function generateBlogPost() {
    // Handle generating
    shopify.toast.show('Blog post template generated')
  }

  return (
    <Page title="My Shopify App">
      <Layout>
        <Layout.Section>
          <Card sectioned>
            <Text as="h2" variant="headingMd">
              Welcome to your Shopify App! 🎉
            </Text>
            <Text as="p">
              This app is built with React and Shopify App Bridge.
            </Text>
            <div style={{ marginTop: '1rem' }}>
              <Button primary onClick={generateBlogPost}>
                Generate Blog Post
              </Button>
            </div>
          </Card>
        </Layout.Section>
      </Layout>
    </Page>
  )
}

export default App
