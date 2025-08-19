import React, { useState } from 'react'
import { Page, Card, Form, FormLayout, TextField, Button } from '@shopify/polaris'

export default function Settings() {
    const [shopName, setShopName] = useState('');

    return (
        <Page title="Cài đặt">
            <Card sectioned>
                <Form onSubmit={() => alert(`Lưu: ${shopName}`)}>
                    <FormLayout>
                        <TextField
                            label="Tên cửa hàng"
                            value={shopName}
                            onChange={setShopName}
                            autoComplete="off"
                        />
                        <Button submit primary>Lưu</Button>
                    </FormLayout>
                </Form>
            </Card>
        </Page>
    );
}