
import React from "react";
import { Page, Card, DataTable } from '@shopify/polaris'

export default function Dashboard() {
    const rows = [
        [
            'John Doe',
            'john.doe@example.com',
            '1234567890',
            '1234567890',
        ],
        [
            'Jane Doe',
            'jane.doe@example.com',
            '1234567890',
            '1234567890',
        ],
    ]
    return (
        <Page title="Settings">
            <Card>
                <DataTable
                    columnContentTypes={['text', 'text', 'text', 'text']}
                    headings={['Name', 'Email', 'Phone', 'Address']}
                    rows={rows}
                />
            </Card>
        </Page>
    )
}