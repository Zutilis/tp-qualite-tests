import { expect, test } from '@playwright/test';

test.describe('Parcours utilisateur - gestion des tâches', () => {
    test('un utilisateur crée une tâche et la voit apparaître dans la liste', async ({ page }) => {
        await page.goto('/');

        await page.getByTestId('task-title-input').fill('Préparer la démonstration E2E');
        await page.getByTestId('task-priority-select').selectOption('high');
        await page.getByTestId('task-submit').click();

        await expect(page.getByTestId('flash-status')).toHaveText('Tâche créée avec succès.');

        const item = page.getByTestId('task-item').filter({ hasText: 'Préparer la démonstration E2E' });
        await expect(item).toBeVisible();
        await expect(item.locator('.badge.priority-high')).toHaveText('High');
    });

    test('une tâche en retard disparaît du compteur une fois terminée', async ({ page }) => {
        await page.goto('/');

        const pastDate = new Date();
        pastDate.setDate(pastDate.getDate() - 3);
        const pastDateValue = pastDate.toISOString().slice(0, 10);

        await page.getByTestId('task-title-input').fill('Tâche volontairement en retard');
        await page.locator('input[name="due_date"]').fill(pastDateValue);
        await page.getByTestId('task-submit').click();

        const item = page.getByTestId('task-item').filter({ hasText: 'Tâche volontairement en retard' });
        await expect(item.getByTestId('late-badge')).toBeVisible();

        const lateCountBefore = await page.getByTestId('late-count').textContent();
        expect(lateCountBefore).toContain('1');

        await item.getByTestId('task-complete-button').click();

        await expect(page.getByTestId('flash-status')).toHaveText('Tâche marquée comme terminée.');
        const lateCountAfter = await page.getByTestId('late-count').textContent();
        expect(lateCountAfter).toContain('0');

        await page.getByRole('link', { name: 'Terminées' }).click();
        await expect(
            page.getByTestId('task-item').filter({ hasText: 'Tâche volontairement en retard' })
        ).toBeVisible();
    });
});
