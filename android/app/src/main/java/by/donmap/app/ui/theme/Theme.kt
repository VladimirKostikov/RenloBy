package by.donmap.app.ui.theme

import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.graphics.Color

private val DonMapColors = lightColorScheme(
    primary = Color(0xFF2563EB),
    secondary = Color(0xFF64748B),
    background = Color(0xFFF8FAFC),
    surface = Color.White,
)

@Composable
fun DonMapTheme(content: @Composable () -> Unit) {
    MaterialTheme(
        colorScheme = DonMapColors,
        content = content,
    )
}
