"""Add register_user procedure

Revision ID: 86503686f0e0
Revises: 12b2b9c14824
Create Date: 2025-03-31 10:39:58.340786

"""
from typing import Sequence, Union

from alembic import op
import sqlalchemy as sa


# revision identifiers, used by Alembic.
revision: str = '86503686f0e0'
down_revision: Union[str, None] = '12b2b9c14824'
branch_labels: Union[str, Sequence[str], None] = None
depends_on: Union[str, Sequence[str], None] = None


def upgrade() -> None:
    """Upgrade schema."""
    pass


def downgrade() -> None:
    """Downgrade schema."""
    pass
