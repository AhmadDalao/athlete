import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:throughline_mobile/src/core/router/app_router.dart';
import 'package:throughline_mobile/src/core/theme/app_theme.dart';

class ThroughlineApp extends ConsumerWidget {
  const ThroughlineApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final themeMode = ref.watch(themeControllerProvider);
    final router = ref.watch(appRouterProvider);

    return MaterialApp.router(
      title: 'Throughline',
      debugShowCheckedModeBanner: false,
      theme: ThroughlineTheme.light,
      darkTheme: ThroughlineTheme.dark,
      themeMode: themeMode,
      routerConfig: router,
    );
  }
}
