
import { 
  Page, 
  Card, 
  BlockStack,
  InlineGrid,
  Box,
  Text,
  RadioButton,
  TextField,
  Select,
  RangeSlider,
  ColorPicker,
  Button,
  Badge,
  InlineStack,
  ButtonGroup,
  Popover
} from '@shopify/polaris'
import { 
  TextAlignCenterIcon, 
  TextAlignLeftIcon, 
  TextAlignRightIcon 
} from '@shopify/polaris-icons'
import { useState, useCallback } from 'react'
import quoteSnapLogo from './assets/logoquotesnap.png'

function App() {
  
  // Quote button configuration states
  const [displayRule, setDisplayRule] = useState('all')
  const [position, setPosition] = useState('under-button')
  const [buttonLabel, setButtonLabel] = useState('Request for quote')
  const [alignment, setAlignment] = useState('center')
  const [fontSize, setFontSize] = useState(15)
  const [cornerRadius, setCornerRadius] = useState(15)
  const [textColor, setTextColor] = useState({
    hue: 0,
    brightness: 1,
    saturation: 0,
  })
  const [buttonColor, setButtonColor] = useState({
    hue: 39,
    brightness: 1,
    saturation: 1,
  })
  const [isActive, setIsActive] = useState(true)
  
  // Popover states
  const [textColorPopoverActive, setTextColorPopoverActive] = useState(false)
  const [buttonColorPopoverActive, setButtonColorPopoverActive] = useState(false)





  const positionOptions = [
    { label: 'Under button "Add To Cart"', value: 'under-button' },
    { label: 'Above button "Add To Cart"', value: 'above-button' },
    { label: 'Replace button "Add To Cart"', value: 'replace-button' },
  ]

  // Helper function to convert HSB to hex
  const hsbToHex = (hsb) => {
    const { hue, saturation, brightness } = hsb

    const h = hue / 360
    const s = saturation
    const v = brightness

    const i = Math.floor(h * 6)
    const f = h * 6 - i
    const p = v * (1 - s)
    const q = v * (1 - f * s)
    const t = v * (1 - (1 - f) * s)

    let r, g, b
    switch (i % 6) {
      case 0: r = v; g = t; b = p; break
      case 1: r = q; g = v; b = p; break
      case 2: r = p; g = v; b = t; break
      case 3: r = p; g = q; b = v; break
      case 4: r = t; g = p; b = v; break
      case 5: r = v; g = p; b = q; break
      default: r = 0; g = 0; b = 0
    }

    const toHex = (c) => {
      const hex = Math.round(c * 255).toString(16)
      return hex.length === 1 ? '0' + hex : hex
    }

    const result = `#${toHex(r)}${toHex(g)}${toHex(b)}`

    return result
  }

  // Helper function to convert hex to HSB
  const hexToHsb = (hex) => {
    const r = parseInt(hex.slice(1, 3), 16) / 255
    const g = parseInt(hex.slice(3, 5), 16) / 255
    const b = parseInt(hex.slice(5, 7), 16) / 255

    const max = Math.max(r, g, b)
    const min = Math.min(r, g, b)
    const diff = max - min

    let hue = 0
    if (diff !== 0) {
      switch (max) {
        case r:
          hue = ((g - b) / diff) % 6
          break
        case g:
          hue = (b - r) / diff + 2
          break
        case b:
          hue = (r - g) / diff + 4
          break
      }
    }
    hue = Math.round(hue * 60)
    if (hue < 0) hue += 360

    const saturation = max === 0 ? 0 : diff / max
    const brightness = max

    return {
      hue,
      saturation,
      brightness
    }
  }

  // Handler functions
  const handleDisplayRuleChange = useCallback((checked, id) => {
    if (checked) {
      setDisplayRule(id)
    }
  }, [])

  const handlePositionChange = useCallback((value) => setPosition(value), [])
  const handleButtonLabelChange = useCallback((value) => setButtonLabel(value), [])

  const handleFontSizeChange = useCallback((value) => setFontSize(value), [])
  const handleCornerRadiusChange = useCallback((value) => setCornerRadius(value), [])
  const handleTextColorChange = useCallback((color) => setTextColor(color), [])
  const handleButtonColorChange = useCallback((color) => setButtonColor(color), [])
  const handleActiveStatusChange = useCallback(() => setIsActive(prev => !prev), [])

  return (
    <Page
    >
      <InlineGrid columns={{ xs: 1, md: "2fr 1fr" }} gap="400">
        <BlockStack gap="400">
          <Card roundedAbove="sm">
            <BlockStack gap="400">
              <Text as="h2" variant="headingSm" fontWeight="medium">
                Display rule
              </Text>
              
              <Box paddingBlockStart="200">
                <Text as="p" variant="bodyMd" color="subdued">
                  Position on product page
                </Text>
              </Box>
              
              <Select
                options={positionOptions}
                onChange={handlePositionChange}
                value={position}
              />
              
              <BlockStack gap="300">
                <RadioButton
                  label="All products"
                  checked={displayRule === 'all'}
                  id="all"
                  name="displayRule"
                  onChange={handleDisplayRuleChange}
                />
                <RadioButton
                  label="Specific products"
                  checked={displayRule === 'specific'}
                  id="specific"
                  name="displayRule"
                  onChange={handleDisplayRuleChange}
                />
                <RadioButton
                  label="Group products"
                  checked={displayRule === 'group'}
                  id="group"
                  name="displayRule"
                  onChange={handleDisplayRuleChange}
                />
              </BlockStack>
            </BlockStack>
          </Card>
          <Card roundedAbove="sm">
            <BlockStack gap="400">
              <Text as="h2" variant="headingSm" fontWeight="medium">
                Style
              </Text>
              
              <TextField
                label="Button Label"
                value={buttonLabel}
                onChange={handleButtonLabelChange}
                autoComplete="off"
              />
              
              <Box>
                <Text as="p" variant="bodyMd" fontWeight="medium">
                  Alignment
                </Text>
                <Box paddingBlockStart="200">
                  <ButtonGroup segmented>
                    <Button
                      pressed={alignment === 'flex-start'}
                      onClick={() => setAlignment('flex-start')}
                      icon={TextAlignLeftIcon}
                    />
                    <Button
                      pressed={alignment === 'center'}
                      onClick={() => setAlignment('center')}
                      icon={TextAlignCenterIcon}
                    />
                    <Button
                      pressed={alignment === 'flex-end'}
                      onClick={() => setAlignment('flex-end')}
                      icon={TextAlignRightIcon}
                    />
                  </ButtonGroup>
                </Box>
              </Box>
              
              <Box>
                <Text as="p" variant="bodyMd" fontWeight="medium">
                  Font size
                </Text>
                <Box paddingBlockStart="200">
                  <InlineStack gap="200" blockAlign="center">
                    <Box style={{ flex: 1 }}>
                      <RangeSlider
                        label=""
                        value={fontSize}
                        onChange={handleFontSizeChange}
                        min={10}
                        max={30}
                      />
                    </Box>
                    <Box style={{ minWidth: '80px' }}>
                      <TextField
                        value={fontSize.toString()}
                        onChange={(value) => {
                          const num = parseInt(value) || 10
                          if (num >= 10 && num <= 30) {
                            setFontSize(num)
                          }
                        }}
                        suffix="px"
                        autoComplete="off"
                      />
                    </Box>
                  </InlineStack>
                </Box>
              </Box>
              
              <Box>
                <Text as="p" variant="bodyMd" fontWeight="medium">
                  Corner radius
                </Text>
                <Box paddingBlockStart="200">
                  <InlineStack gap="200" blockAlign="center">
                    <Box style={{ flex: 1 }}>
                      <RangeSlider
                        label=""
                        value={cornerRadius}
                        onChange={handleCornerRadiusChange}
                        min={0}
                        max={50}
                      />
                    </Box>
                    <Box style={{ minWidth: '80px' }}>
                      <TextField
                        value={cornerRadius.toString()}
                        onChange={(value) => {
                          const num = parseInt(value) || 0
                          if (num >= 0 && num <= 50) {
                            setCornerRadius(num)
                          }
                        }}
                        suffix="px"
                        autoComplete="off"
                      />
                    </Box>
                  </InlineStack>
                </Box>
              </Box>
              
              <InlineGrid columns={2} gap="400">
                <Box>
                  <Text as="p" variant="bodyMd" fontWeight="medium">
                    Text color
                  </Text>
                  <Box paddingBlockStart="200">
                    <InlineStack gap="200" blockAlign="center">
                      <Popover
                        active={textColorPopoverActive}
                        activator={
                          <Button
                            onClick={() => setTextColorPopoverActive(!textColorPopoverActive)}
                            style={{
                              backgroundColor: hsbToHex(textColor),
                              width: '32px',
                              height: '32px',
                              minHeight: '32px',
                              padding: 0,
                              border: '1px solid #ccc'
                            }}
                          />
                        }
                        onClose={() => setTextColorPopoverActive(false)}
                      >
                        <Box padding="400">
                          <ColorPicker
                            onChange={handleTextColorChange}
                            color={textColor}
                          />
                        </Box>
                      </Popover>
                      <TextField
                        value={hsbToHex(textColor).slice(1)}
                        onChange={(value) => {
                          const hex = `#${value}`
                          const hexRegex = /^#[0-9A-Fa-f]{6}$/
                          if (hexRegex.exec(hex)) {
                            setTextColor(hexToHsb(hex))
                          }
                        }}
                        prefix="#"
                        autoComplete="off"
                      />
                    </InlineStack>
                  </Box>
                </Box>
                
                <Box>
                  <Text as="p" variant="bodyMd" fontWeight="medium">
                    Button color
                  </Text>
                  <Box paddingBlockStart="200">
                    <InlineStack gap="200" blockAlign="center">
                      <Popover
                        active={buttonColorPopoverActive}
                        activator={
                          <Button
                            onClick={() => setButtonColorPopoverActive(!buttonColorPopoverActive)}
                            style={{
                              backgroundColor: hsbToHex(buttonColor),
                              width: '32px',
                              height: '32px',
                              minHeight: '32px',
                              padding: 0,
                              border: '1px solid #ccc'
                            }}
                          />
                        }
                        onClose={() => setButtonColorPopoverActive(false)}
                      >
                        <Box padding="400">
                          <ColorPicker
                            onChange={handleButtonColorChange}
                            color={buttonColor}
                          />
                        </Box>
                      </Popover>
                      <TextField
                        value={hsbToHex(buttonColor).slice(1)}
                        onChange={(value) => {
                          const hex = `#${value}`
                          const hexRegex = /^#[0-9A-Fa-f]{6}$/
                          if (hexRegex.exec(hex)) {
                            setButtonColor(hexToHsb(hex))
                          }
                        }}
                        prefix="#"
                        autoComplete="off"
                      />
                    </InlineStack>
                  </Box>
                </Box>
              </InlineGrid>
            </BlockStack>
          </Card>
        </BlockStack>
        <BlockStack gap={{ xs: "400", md: "200" }}>
          <Card roundedAbove="sm">
            <BlockStack gap="400">
              <InlineStack align="space-between" blockAlign="center">
                <Text as="h2" variant="headingSm" fontWeight="medium">
                  Active status
                </Text>
                <Badge tone={isActive ? 'success' : 'critical'}>
                  {isActive ? 'On' : 'Off'}
                </Badge>
              </InlineStack>
              
              <Text as="p" variant="bodyMd" color="subdued">
                Show a Request For Quote button on all pages via store front.
              </Text>
              
              <Button
                onClick={handleActiveStatusChange}
                variant={isActive ? 'secondary' : 'primary'}
              >
                Turn {isActive ? 'off' : 'on'}
              </Button>
            </BlockStack>
          </Card>
          <Card roundedAbove="sm">
            <BlockStack gap="400">
              <Text as="h2" variant="headingSm" fontWeight="medium">Preview</Text>
              
              {/* Display Rule Info */}
              <Box paddingBlockEnd="200">
                <Text as="p" variant="bodyMd" color="subdued">
                  {displayRule === 'all' && 'Showing on all products'}
                  {displayRule === 'specific' && 'Showing on specific products only'}
                  {displayRule === 'group' && 'Showing on product groups only'}
                  {' • '}
                  {position === 'under-button' && 'Under "Add to Cart"'}
                  {position === 'above-button' && 'Above "Add to Cart"'}
                  {position === 'replace-button' && 'Replaces "Add to Cart"'}
                </Text>
              </Box>
              

              
              <Box 
                background="bg-surface-secondary" 
                padding="400" 
                borderRadius="200"
              >
                <BlockStack gap="400" align="center">
                  {/* Product Image */}
                  <Box
                    borderRadius="200"
                    style={{
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                    }}
                  >
                    <img 
                      src={quoteSnapLogo} 
                      alt="Quote Snap"
                      style={{
                        width: '120px',
                        height: '120px',
                        objectFit: 'contain',
                        borderRadius: '8px'
                      }}
                    />
                  </Box>
                  
                  {/* Product Title */}
                  <Text as="h3" variant="headingMd" fontWeight="medium" alignment="center">
                    Quote Snap
                  </Text>
                  
                  {/* Buttons */}
                  <Box style={{ width: '100%', minWidth: '200px' }}>
                    <BlockStack gap="200" inlineAlign="stretch">
                                        {/* Above Add to Cart */}
                    {position === 'above-button' && (
                      <Box style={{ display: 'flex', justifyContent: alignment }}>
                        <div
                          style={{
                            backgroundColor: hsbToHex(buttonColor),
                            color: hsbToHex(textColor),
                            fontSize: `${fontSize}px`,
                            borderRadius: `${cornerRadius}px`,
                            padding: '12px 20px',
                            border: 'none',
                            cursor: 'pointer',
                            fontWeight: '500',
                            textAlign: 'center',
                            minWidth: '120px',
                          }}
                        >
                          {buttonLabel}
                        </div>
                      </Box>
                    )}
                    
                    {/* Add to Cart (only show if not replaced) */}
                    {position !== 'replace-button' && (
                      <Button size="large" variant="secondary">
                        Add to cart
                      </Button>
                    )}
                    
                    {/* Under Add to Cart */}
                    {position === 'under-button' && (
                      <Box style={{  : 'flex', justifyContent: alignment }}>
                        <div
                          style={{
                            backgroundColor: hsbToHex(buttonColor),
                            color: hsbToHex(textColor),
                            fontSize: `${fontSize}px`,
                            borderRadius: `${cornerRadius}px`,
                            padding: '12px 20px',
                            border: 'none',
                            cursor: 'pointer',
                            fontWeight: '500',
                            textAlign: 'center',
                            minWidth: '120px',
                          }}
                        >
                          {buttonLabel}
                        </div>
                      </Box>
                    )}
                    
                    {/* Replace Add to Cart */}
                    {position === 'replace-button' && (
                      <Box style={{ display: 'flex', justifyContent: alignment }}>
                        <div
                          style={{
                            backgroundColor: hsbToHex(buttonColor),
                            color: hsbToHex(textColor),
                            fontSize: `${fontSize}px`,
                            borderRadius: `${cornerRadius}px`,
                            padding: '12px 20px',
                            border: 'none',
                            cursor: 'pointer',
                            fontWeight: '500',
                            textAlign: 'center',
                            minWidth: '120px',
                          }}
                        >
                          {buttonLabel}
                        </div>
                      </Box>
                    )}
                    
                    <Button size="large" variant="tertiary">
                      Buy it now
                    </Button>
                    </BlockStack>
                  </Box>
                </BlockStack>
              </Box>
            </BlockStack>
          </Card>
        </BlockStack>
      </InlineGrid>
    </Page>
  )
}

export default App
