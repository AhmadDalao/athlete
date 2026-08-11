import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';

class ThroughlineColors {
  const ThroughlineColors._();

  static const graphite = Color(0xFF0B0E0D);
  static const graphiteRaised = Color(0xFF141917);
  static const lime = Color(0xFFB9F34A);
  static const emerald = Color(0xFF10A779);
  static const gold = Color(0xFFE7B84B);
  static const cyan = Color(0xFF43C6D9);
  static const cream = Color(0xFFF5F1E8);
  static const ink = Color(0xFF151917);
  static const muted = Color(0xFF8D9792);
  static const danger = Color(0xFFFF6B68);
}

class ThroughlineTheme {
  const ThroughlineTheme._();

  static ThemeData get dark => _build(
    brightness: Brightness.dark,
    surface: ThroughlineColors.graphite,
    card: ThroughlineColors.graphiteRaised,
    text: Colors.white,
  );

  static ThemeData get light => _build(
    brightness: Brightness.light,
    surface: ThroughlineColors.cream,
    card: Colors.white,
    text: ThroughlineColors.ink,
  );

  static ThemeData _build({
    required Brightness brightness,
    required Color surface,
    required Color card,
    required Color text,
  }) {
    final scheme = ColorScheme.fromSeed(
      seedColor: ThroughlineColors.lime,
      brightness: brightness,
      primary: ThroughlineColors.lime,
      secondary: ThroughlineColors.emerald,
      surface: surface,
      error: ThroughlineColors.danger,
    );
    final base = ThemeData(
      useMaterial3: true,
      brightness: brightness,
      colorScheme: scheme,
      scaffoldBackgroundColor: surface,
      fontFamily: 'SpaceGrotesk',
    );

    return base.copyWith(
      textTheme: base.textTheme.apply(
        fontFamily: 'SpaceGrotesk',
        bodyColor: text,
        displayColor: text,
      ),
      cardTheme: CardThemeData(
        color: card,
        elevation: 0,
        margin: EdgeInsets.zero,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(24),
          side: BorderSide(color: text.withValues(alpha: 0.09)),
        ),
      ),
      appBarTheme: AppBarTheme(
        backgroundColor: surface,
        foregroundColor: text,
        elevation: 0,
        scrolledUnderElevation: 0,
        centerTitle: false,
      ),
      navigationBarTheme: NavigationBarThemeData(
        height: 74,
        backgroundColor: card,
        indicatorColor: ThroughlineColors.lime,
        labelTextStyle: WidgetStateProperty.resolveWith(
          (states) => TextStyle(
            color: states.contains(WidgetState.selected)
                ? text
                : ThroughlineColors.muted,
            fontSize: 11,
            fontWeight: FontWeight.w700,
          ),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: brightness == Brightness.dark
            ? Colors.white.withValues(alpha: 0.055)
            : Colors.white,
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 18,
          vertical: 17,
        ),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(18),
          borderSide: BorderSide(color: text.withValues(alpha: 0.12)),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(18),
          borderSide: BorderSide(color: text.withValues(alpha: 0.12)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(18),
          borderSide: const BorderSide(
            color: ThroughlineColors.lime,
            width: 1.5,
          ),
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: ThroughlineColors.lime,
          foregroundColor: ThroughlineColors.graphite,
          minimumSize: const Size.fromHeight(54),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(18),
          ),
          textStyle: const TextStyle(fontWeight: FontWeight.w800, fontSize: 15),
        ),
      ),
      chipTheme: base.chipTheme.copyWith(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(999)),
        side: BorderSide(color: text.withValues(alpha: 0.1)),
        labelStyle: const TextStyle(fontWeight: FontWeight.w700, fontSize: 12),
      ),
      dividerColor: text.withValues(alpha: 0.1),
    );
  }
}

class ThemeController extends StateNotifier<ThemeMode> {
  ThemeController() : super(ThemeMode.system) {
    _restore();
  }

  static const preferenceKey = 'throughline_theme';

  Future<void> _restore() async {
    final preferences = await SharedPreferences.getInstance();
    state = _parse(preferences.getString(preferenceKey));
  }

  Future<void> setMode(ThemeMode mode) async {
    state = mode;
    final preferences = await SharedPreferences.getInstance();
    await preferences.setString(preferenceKey, mode.name);
  }

  Future<void> useServerPreference(String? value) async {
    if (value == null) return;
    await setMode(_parse(value));
  }

  ThemeMode _parse(String? value) => switch (value) {
    'dark' => ThemeMode.dark,
    'light' => ThemeMode.light,
    _ => ThemeMode.system,
  };
}

final themeControllerProvider =
    StateNotifierProvider<ThemeController, ThemeMode>(
      (ref) => ThemeController(),
    );
