"""Add register_user procedure

Revision ID: 12b2b9c14824
Revises: 715feb1041be
Create Date: 2025-03-31 10:05:35.670506

"""
from typing import Sequence, Union

from alembic import op
import sqlalchemy as sa


# revision identifiers, used by Alembic.
revision: str = '12b2b9c14824'
down_revision: Union[str, None] = '715feb1041be'
branch_labels: Union[str, Sequence[str], None] = None
depends_on: Union[str, Sequence[str], None] = None


def upgrade() -> None:
    """Upgrade schema."""
    pass


def downgrade() -> None:
    """Downgrade schema."""
    pass
